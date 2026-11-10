<?php
namespace App\Controller;

use LoanScheduleData;
use PaymentMethodData;
use Database;
use ViewEngine;

class PaymentController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    /**
     * Pagos del Día: Pagos agendados para la fecha actual
     */
    public function today() {
        LoanScheduleData::updateOverdue();
        Database::getPdo()->exec("UPDATE loan SET status = 'overdue' WHERE status = 'active' AND id IN (SELECT DISTINCT loan_id FROM loan_schedule WHERE status = 'overdue')");

        $schedules = LoanScheduleData::getTodayScheduled(null);
        $payment_methods = PaymentMethodData::getAllActive();

        $total_due = 0.00;
        $total_paid_today = 0.00;
        foreach ($schedules as $s) {
            $total_due += (float)$s->balance;
            $total_paid_today += (float)$s->paid_amount;
        }

        ViewEngine::render('payments/today.html.twig', [
            'schedules'        => $schedules,
            'payment_methods'  => $payment_methods,
            'total_due'        => $total_due,
            'total_paid_today' => $total_paid_today,
            'count_schedules'  => count($schedules),
            'active_menu'      => 'payments_today'
        ]);
    }

    /**
     * Pagos Atrasados: Cuotas vencidas para cobro y seguimiento
     */
    public function overdue() {
        LoanScheduleData::updateOverdue();
        Database::getPdo()->exec("UPDATE loan SET status = 'overdue' WHERE status = 'active' AND id IN (SELECT DISTINCT loan_id FROM loan_schedule WHERE status = 'overdue')");

        $schedules = LoanScheduleData::getOverdueScheduled(null);
        $payment_methods = PaymentMethodData::getAllActive();

        $total_balance = 0.00;
        foreach ($schedules as $s) {
            $total_balance += (float)$s->balance;
        }

        ViewEngine::render('payments/overdue.html.twig', [
            'schedules'        => $schedules,
            'payment_methods'  => $payment_methods,
            'total_balance'    => $total_balance,
            'count_schedules'  => count($schedules),
            'active_menu'      => 'payments_overdue'
        ]);
    }
}
