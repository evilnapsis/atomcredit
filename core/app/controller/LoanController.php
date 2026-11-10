<?php
namespace App\Controller;

use LoanData;
use LoanScheduleData;
use LoanProductData;
use ClientData;
use PaymentData;
use PaymentMethodData;
use Database;
use ViewEngine;
use Req;
use Response;
use UserData;
use App\Service\AuthService;
use App\Service\PdfService;

class LoanController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function index() {
        LoanScheduleData::updateOverdue();
        Database::getPdo()->exec("UPDATE loan SET status = 'overdue' WHERE status = 'active' AND id IN (SELECT DISTINCT loan_id FROM loan_schedule WHERE status = 'overdue')");

        $status_filter = Req::get('status', 'active');
        $client_filter = (int)Req::get('client_id', 0);
        $client = null;

        if ($client_filter > 0) {
            $loans = LoanData::getByClientId($client_filter);
            $client = ClientData::getById($client_filter);
        } elseif ($status_filter !== 'all') {
            $loans = LoanData::getByStatus($status_filter);
        } else {
            $loans = LoanData::getAll();
        }
        $clients = ClientData::getAllActive();

        $loan_products = LoanProductData::getAllActive();

        ViewEngine::render('loans/index.html.twig', [
            'loans'         => $loans,
            'client'        => $client,
            'client_filter' => $client_filter,
            'status_filter' => $status_filter,
            'loan_products' => $loan_products,
            'clients'       => $clients
        ]);
    }

    public function new() {
        $loan_products = LoanProductData::getAllActive();
        $clients       = ClientData::getAllActive();
        $users         = UserData::getAllActive();
        $client_id     = (int)Req::get('client_id', 0);

        ViewEngine::render('loans/new.html.twig', [
            'loan_products' => $loan_products,
            'clients'       => $clients,
            'users'         => $users,
            'client_id'     => $client_id,
            'active_menu'   => 'loans'
        ]);
    }

    public function pdf(array $vars) {
        $id = (int)($vars['id'] ?? 0);
        $loan = LoanData::getById($id);
        if (!$loan) {
            Response::redirect(($this->baseFolder ?: '') . '/loans');
            return;
        }

        $client          = ClientData::getById($loan->client_id);
        $schedule        = LoanScheduleData::getByLoanId($loan->id);
        $total_paid      = PaymentData::sumByLoanId($loan->id);
        $pending_balance = max(0.00, (float)$loan->total_amount - $total_paid);

        PdfService::loanDetailPdf($loan, $client, $schedule, $total_paid, $pending_balance);
    }

    public function show(array $vars) {
        $id = (int)($vars['id'] ?? 0);
        LoanScheduleData::updateOverdue();

        $loan = LoanData::getById($id);
        if (!$loan) {
            Response::redirect(($this->baseFolder ?: '') . '/loans');
            return;
        }

        $client          = ClientData::getById($loan->client_id);
        $schedule        = LoanScheduleData::getByLoanId($loan->id);
        $payments        = PaymentData::getByLoanId($loan->id);
        $total_paid      = PaymentData::sumByLoanId($loan->id);
        $pending_balance = max(0.00, (float)$loan->total_amount - $total_paid);
        $payment_methods = PaymentMethodData::getAllActive();
        $next_pending    = LoanScheduleData::getNextPending($loan->id);

        ViewEngine::render('loans/show.html.twig', [
            'loan'            => $loan,
            'client'          => $client,
            'schedule'        => $schedule,
            'payments'        => $payments,
            'total_paid'      => $total_paid,
            'pending_balance' => $pending_balance,
            'payment_methods' => $payment_methods,
            'next_pending'    => $next_pending
        ]);
    }

    public function create() {
        $l = new LoanData();
        $l->client_id = (int)Req::post('client_id');
        $l->loan_product_id = (int)Req::post('loan_product_id');

        // Los préstamos los asigna el administrador mediante el campo user_id
        $assignedUserId = (int)Req::post('user_id', 0);
        $l->user_id = $assignedUserId > 0 ? $assignedUserId : (int)($_SESSION['user_id'] ?? 1);

        $l->amount = (float)Req::post('amount');
        $l->interest_rate = (float)Req::post('interest_rate');
        $l->term = (int)Req::post('term');
        $l->frequency = (int)Req::post('frequency');
        $l->disbursement_date = Req::post('disbursement_date', date('Y-m-d'));
        $l->start_date = Req::post('start_date', date('Y-m-d'));
        $l->notes = Req::post('notes', '');
        $l->status = 'active';

        $id = $l->add();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

        if ($isAjax) {
            if ($id > 0) {
                Response::json(['success' => true, 'id' => $id, 'message' => 'Préstamo creado con éxito']);
            } else {
                Response::json(['success' => false, 'message' => 'No se pudo crear el préstamo'], 500);
            }
            return;
        }

        if ($id > 0) {
            $_SESSION['success'] = '¡Préstamo creado y asignado con éxito!';
            Response::redirect(($this->baseFolder ?: '') . '/loans/' . $id);
        } else {
            $_SESSION['error'] = 'No se pudo crear el préstamo.';
            Response::redirect(($this->baseFolder ?: '') . '/loans');
        }
    }

    public function pay() {
        $loan_id = (int)Req::post('loan_id');
        $schedule_id = (int)Req::post('loan_schedule_id', 0);
        $amount = (float)Req::post('amount');
        $payment_method_id = (int)Req::post('payment_method_id', 1);
        $notes = Req::post('notes', '');

        if ($amount <= 0 || $loan_id <= 0) {
            Response::json(['success' => false, 'message' => 'El monto debe ser mayor a cero'], 400);
            return;
        }

        $pay = new PaymentData();
        $pay->loan_id = $loan_id;
        $pay->loan_schedule_id = $schedule_id > 0 ? $schedule_id : null;
        $pay->user_id = (int)($_SESSION['user_id'] ?? 1);
        $pay->payment_method_id = $payment_method_id;
        $pay->amount = $amount;
        $pay->notes = $notes;
        $pay->add();

        // Actualizar cuota específica si se especificó
        if ($schedule_id > 0) {
            $sch = LoanScheduleData::getById($schedule_id);
            if ($sch) {
                $sch->paid_amount = round((float)$sch->paid_amount + $amount, 2);
                $sch->balance = max(0.00, round((float)$sch->amount - (float)$sch->paid_amount, 2));
                if ($sch->paid_amount >= $sch->amount) {
                    $sch->status = 'paid';
                } else {
                    $sch->status = 'partial';
                }
                $sch->update();
            }
        } else {
            // Distribución automática al siguiente pendiente si no se seleccionó cuota
            $next = LoanScheduleData::getNextPending($loan_id);
            if ($next) {
                $next->paid_amount = round((float)$next->paid_amount + $amount, 2);
                $next->balance = max(0.00, round((float)$next->amount - (float)$next->paid_amount, 2));
                if ($next->paid_amount >= $next->amount) {
                    $next->status = 'paid';
                } else {
                    $next->status = 'partial';
                }
                $next->update();
            }
        }

        // Verificar si el préstamo se liquidó por completo o si ya no tiene mora
        $total_paid = PaymentData::sumByLoanId($loan_id);
        $loan = LoanData::getById($loan_id);
        if ($loan) {
            if ($total_paid >= (float)$loan->total_amount) {
                $loan->updateStatus('paid');
            } elseif ($loan->status === 'overdue') {
                $hasOverdue = (int)Database::getPdo()->query("SELECT COUNT(*) FROM loan_schedule WHERE loan_id = {$loan_id} AND status = 'overdue'")->fetchColumn();
                if ($hasOverdue === 0) {
                    $loan->updateStatus('active');
                }
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

        if ($isAjax) {
            Response::json(['success' => true, 'message' => 'Pago registrado correctamente']);
        } else {
            $_SESSION['success'] = 'Pago registrado correctamente';
            $redirect = Req::post('redirect_to', ($this->baseFolder ?: '') . '/loans/' . $loan_id);
            Response::redirect($redirect);
        }
    }

    public function updateStatus() {
        $id = (int)Req::post('id');
        $status = Req::post('status');

        $loan = LoanData::getById($id);
        if ($loan) {
            $loan->updateStatus($status);
            Response::json(['success' => true, 'message' => 'Estado actualizado']);
            return;
        }

        Response::json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
    }
}
