<?php
namespace App\Controller;

use LoanData;
use LoanScheduleData;
use ClientData;
use PaymentData;
use SettingData;
use Database;
use ViewEngine;
use App\Service\AuthService;

class HomeController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function index() {
        // Actualizar cuotas vencidas y estados de mora
        LoanScheduleData::updateOverdue();
        Database::getPdo()->exec("UPDATE loan SET status = 'overdue' WHERE status = 'active' AND id IN (SELECT DISTINCT loan_id FROM loan_schedule WHERE status = 'overdue')");

        $all_loans      = LoanData::getAll();
        $active_loans   = LoanData::getByStatus('active');
        $overdue_loans  = LoanData::getByStatus('overdue');
        $paid_loans     = LoanData::getByStatus('paid');
        $pending_loans  = LoanData::getByStatus('pending');
        $clients        = ClientData::getAllActive();

        $total_capital  = array_sum(array_map(fn($l) => (float)$l->amount, $active_loans));
        $total_overdue  = count($overdue_loans);
        $total_all      = count($all_loans);

        $daily_payments = PaymentData::getDailyPaymentsLast30Days(null);

        $statuses = [
            'active'  => ['label' => 'Activos', 'color' => '#6366f1', 'count' => count($active_loans), 'pct' => $total_all > 0 ? round((count($active_loans) / $total_all) * 100) : 0],
            'overdue' => ['label' => 'En Mora', 'color' => '#ef4444', 'count' => count($overdue_loans), 'pct' => $total_all > 0 ? round((count($overdue_loans) / $total_all) * 100) : 0],
            'paid'    => ['label' => 'Pagados', 'color' => '#10b981', 'count' => count($paid_loans), 'pct' => $total_all > 0 ? round((count($paid_loans) / $total_all) * 100) : 0],
            'pending' => ['label' => 'Pendientes', 'color' => '#f59e0b', 'count' => count($pending_loans), 'pct' => $total_all > 0 ? round((count($pending_loans) / $total_all) * 100) : 0],
        ];

        ViewEngine::render('home/index.html.twig', [
            'all_loans'      => $all_loans,
            'recent_loans'   => array_slice($all_loans, 0, 6),
            'active_loans'   => $active_loans,
            'overdue_loans'  => $overdue_loans,
            'clients'        => $clients,
            'total_capital'  => $total_capital,
            'total_overdue'  => $total_overdue,
            'total_all'      => $total_all,
            'statuses'       => $statuses,
            'daily_payments' => $daily_payments
        ]);
    }
}
