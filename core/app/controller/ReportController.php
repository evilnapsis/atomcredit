<?php
namespace App\Controller;

use LoanData;
use ClientData;
use PaymentData;
use PaymentMethodData;
use ViewEngine;
use Req;
use App\Service\PdfService;

class ReportController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    /**
     * Reporte de Préstamos por Rango de Fechas
     */
    public function loans() {
        $start_date = Req::get('start_date', date('Y-m-01'));
        $end_date   = Req::get('end_date', date('Y-m-d'));
        $status     = Req::get('status', 'all');
        $client_id  = (int)Req::get('client_id', 0);

        $loans   = LoanData::getByDateRange($start_date, $end_date, $status, $client_id);
        $clients = ClientData::getAllActive();

        $total_amount   = 0.00;
        $total_expected = 0.00;
        foreach ($loans as $l) {
            $total_amount   += (float)$l->amount;
            $total_expected += (float)$l->total_amount;
        }

        ViewEngine::render('reports/loans.html.twig', [
            'loans'          => $loans,
            'clients'        => $clients,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'status'         => $status,
            'client_id'      => $client_id,
            'total_amount'   => $total_amount,
            'total_expected' => $total_expected,
            'active_menu'    => 'reports_loans'
        ]);
    }

    /**
     * Exportar Reporte de Préstamos a PDF
     */
    public function loansPdf() {
        $start_date = Req::get('start_date', date('Y-m-01'));
        $end_date   = Req::get('end_date', date('Y-m-d'));
        $status     = Req::get('status', 'all');
        $client_id  = (int)Req::get('client_id', 0);

        $loans = LoanData::getByDateRange($start_date, $end_date, $status, $client_id);
        PdfService::loansReportPdf($loans, $start_date, $end_date, $status);
    }

    /**
     * Reporte de Pagos por Rango de Fechas
     */
    public function payments() {
        $start_date        = Req::get('start_date', date('Y-m-01'));
        $end_date          = Req::get('end_date', date('Y-m-d'));
        $payment_method_id = (int)Req::get('payment_method_id', 0);

        $payments        = PaymentData::getByDateRange($start_date, $end_date, $payment_method_id);
        $payment_methods = PaymentMethodData::getAllActive();

        $total_paid = 0.00;
        foreach ($payments as $p) {
            $total_paid += (float)$p->amount;
        }

        ViewEngine::render('reports/payments.html.twig', [
            'payments'          => $payments,
            'payment_methods'   => $payment_methods,
            'start_date'        => $start_date,
            'end_date'          => $end_date,
            'payment_method_id' => $payment_method_id,
            'total_paid'        => $total_paid,
            'active_menu'       => 'reports_payments'
        ]);
    }

    /**
     * Exportar Reporte de Pagos a PDF
     */
    public function paymentsPdf() {
        $start_date        = Req::get('start_date', date('Y-m-01'));
        $end_date          = Req::get('end_date', date('Y-m-d'));
        $payment_method_id = (int)Req::get('payment_method_id', 0);

        $payments = PaymentData::getByDateRange($start_date, $end_date, $payment_method_id);
        PdfService::paymentsReportPdf($payments, $start_date, $end_date);
    }
}
