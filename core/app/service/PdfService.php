<?php
namespace App\Service;

use FPDF;
use SettingData;

require_once dirname(__DIR__, 3) . "/fpdf/fpdf.php";

class PdfService {

    private static function text($str): string {
        return mb_convert_encoding((string)$str, 'ISO-8859-1', 'UTF-8');
    }

    private static function getCurrency(): string {
        $c = SettingData::currency();
        return $c ? $c : '$';
    }

    private static function getCompanyName(): string {
        return SettingData::getValue('company_name', 'AtomCredit');
    }

    /**
     * Genera PDF individual de un Préstamo con su Agenda / Tabla de Amortización
     */
    public static function loanDetailPdf($loan, $client, array $schedule, float $total_paid, float $pending_balance): void {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();

        $cur = self::getCurrency();
        $company = self::getCompanyName();
        $email = SettingData::getValue('company_email', 'contacto@atomcredit.com');
        $phone = SettingData::getValue('company_phone', '555-0192');

        // Encabezado corporativo
        $pdf->SetFillColor(30, 27, 75); // Deep Indigo #1e1b4b
        $pdf->Rect(15, 15, 186, 22, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetXY(20, 18);
        $pdf->Cell(100, 8, self::text(strtoupper($company) . " - CRONOGRAMA DE PAGOS"), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(20, 26);
        $pdf->Cell(100, 6, self::text("Tel: {$phone}  |  Email: {$email}"), 0, 0, 'L');

        $pdf->SetXY(130, 20);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(65, 6, self::text("PRÉSTAMO #" . str_pad($loan->id, 5, '0', STR_PAD_LEFT)), 0, 1, 'R');
        $pdf->SetXY(130, 26);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(65, 6, self::text("Emisión: " . date('d/m/Y H:i')), 0, 1, 'R');

        $pdf->Ln(10);

        // Bloque de Datos: Cliente y Préstamo (2 Columnas)
        $yStart = $pdf->GetY();

        // Columna Izquierda: Prestatario
        $pdf->SetFillColor(245, 243, 255); // Indigo 50
        $pdf->SetDrawColor(224, 231, 255);
        $pdf->Rect(15, $yStart, 90, 42, 'DF');

        $pdf->SetXY(18, $yStart + 2);
        $pdf->SetTextColor(79, 70, 229);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(84, 6, self::text("DATOS DEL CLIENTE"), 'B', 1, 'L');

        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY(18, $yStart + 9);
        $pdf->Cell(22, 5, self::text("Nombre:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(62, 5, self::text($client->name . " " . $client->lastname), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(18);
        $pdf->Cell(22, 5, self::text("Documento:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(62, 5, self::text($client->document ?: 'N/A'), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(18);
        $pdf->Cell(22, 5, self::text("Teléfono:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(62, 5, self::text($client->phone ?: 'N/A'), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(18);
        $pdf->Cell(22, 5, self::text("Dirección:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(62, 5, self::text(substr($client->address ?: 'N/A', 0, 40)), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(18);
        $pdf->Cell(22, 5, self::text("Ocupación:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(62, 5, self::text($client->occupation ?: 'N/A'), 0, 1, 'L');

        // Columna Derecha: Condiciones del Crédito
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(111, $yStart, 90, 42, 'DF');

        $pdf->SetXY(114, $yStart + 2);
        $pdf->SetTextColor(79, 70, 229);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(84, 6, self::text("CONDICIONES DEL CRÉDITO"), 'B', 1, 'L');

        $freqName = \LoanData::getFrequencyLabel($loan->frequency);
        $statusName = \LoanData::getStatusLabel($loan->status);

        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY(114, $yStart + 9);
        $pdf->Cell(30, 5, self::text("Producto:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(54, 5, self::text($loan->product_name ?: 'Crédito Estándar'), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(114);
        $pdf->Cell(30, 5, self::text("Capital Prestado:"), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(54, 5, self::text("{$cur} " . number_format($loan->amount, 2)), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(114);
        $pdf->Cell(30, 5, self::text("Tasa / Plazo:"), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(54, 5, self::text("{$loan->interest_rate}%  -  {$loan->term} cuotas ({$freqName})"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(114);
        $pdf->Cell(30, 5, self::text("Total a Pagar:"), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(54, 5, self::text("{$cur} " . number_format($loan->total_amount, 2) . "  (Cuota: {$cur} " . number_format($loan->installment_amount, 2) . ")"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetX(114);
        $pdf->Cell(30, 5, self::text("Estado / Saldo:"), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(54, 5, self::text(strtoupper($statusName) . "  -  Pendiente: {$cur} " . number_format($pending_balance, 2)), 0, 1, 'L');

        $pdf->Ln(7);

        // Tabla de Agenda de Pagos / Cronograma de Amortización
        $pdf->SetTextColor(30, 27, 75);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(186, 6, self::text("AGENDA DE PAGOS / TABLA DE AMORTIZACIÓN"), 0, 1, 'L');

        // Cabecera de la tabla
        $pdf->SetFillColor(99, 102, 241); // Indigo #6366f1
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(12, 7, self::text("N°"), 1, 0, 'C', true);
        $pdf->Cell(28, 7, self::text("Vencimiento"), 1, 0, 'C', true);
        $pdf->Cell(28, 7, self::text("Capital"), 1, 0, 'R', true);
        $pdf->Cell(26, 7, self::text("Interés"), 1, 0, 'R', true);
        $pdf->Cell(30, 7, self::text("Cuota Total"), 1, 0, 'R', true);
        $pdf->Cell(28, 7, self::text("Pagado"), 1, 0, 'R', true);
        $pdf->Cell(34, 7, self::text("Estado"), 1, 1, 'C', true);

        // Filas del cronograma
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(40, 40, 40);
        $fill = false;

        $totCapital = 0;
        $totInteres = 0;
        $totCuota = 0;
        $totPagado = 0;

        foreach ($schedule as $sch) {
            $totCapital += (float)$sch->principal;
            $totInteres += (float)$sch->interest;
            $totCuota += (float)$sch->amount;
            $totPagado += (float)$sch->paid_amount;

            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);

            $stLabel = ($sch->status === 'paid') ? 'PAGADO' : (($sch->status === 'overdue') ? 'EN MORA' : (($sch->status === 'partial') ? 'PARCIAL' : 'PENDIENTE'));

            $pdf->Cell(12, 6, $sch->installment_number, 1, 0, 'C', true);
            $pdf->Cell(28, 6, date('d/m/Y', strtotime($sch->due_date)), 1, 0, 'C', true);
            $pdf->Cell(28, 6, "{$cur} " . number_format($sch->principal, 2), 1, 0, 'R', true);
            $pdf->Cell(26, 6, "{$cur} " . number_format($sch->interest, 2), 1, 0, 'R', true);
            $pdf->Cell(30, 6, "{$cur} " . number_format($sch->amount, 2), 1, 0, 'R', true);
            $pdf->Cell(28, 6, "{$cur} " . number_format($sch->paid_amount, 2), 1, 0, 'R', true);

            // Color para el estado
            if ($sch->status === 'paid') {
                $pdf->SetTextColor(4, 120, 87);
            } elseif ($sch->status === 'overdue') {
                $pdf->SetTextColor(220, 38, 38);
            } else {
                $pdf->SetTextColor(100, 116, 139);
            }
            $pdf->Cell(34, 6, self::text($stLabel), 1, 1, 'C', true);
            $pdf->SetTextColor(40, 40, 40);

            $fill = !$fill;
        }

        // Fila de Totales
        $pdf->SetFillColor(224, 231, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(40, 7, self::text("TOTALES:"), 1, 0, 'C', true);
        $pdf->Cell(28, 7, "{$cur} " . number_format($totCapital, 2), 1, 0, 'R', true);
        $pdf->Cell(26, 7, "{$cur} " . number_format($totInteres, 2), 1, 0, 'R', true);
        $pdf->Cell(30, 7, "{$cur} " . number_format($totCuota, 2), 1, 0, 'R', true);
        $pdf->Cell(28, 7, "{$cur} " . number_format($totPagado, 2), 1, 0, 'R', true);
        $pdf->Cell(34, 7, "", 1, 1, 'C', true);

        // Bloque de Firmas
        $pdf->Ln(18);
        $yFirma = $pdf->GetY();
        if ($yFirma > 240) {
            $pdf->AddPage();
            $yFirma = $pdf->GetY() + 10;
        }

        $pdf->Line(25, $yFirma, 85, $yFirma);
        $pdf->Line(125, $yFirma, 185, $yFirma);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY(25, $yFirma + 2);
        $pdf->Cell(60, 4, self::text("FIRMA DEL PRESTATARIO"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(25);
        $pdf->Cell(60, 4, self::text($client->name . " " . $client->lastname), 0, 1, 'C');
        $pdf->SetX(25);
        $pdf->Cell(60, 4, self::text("Doc: " . ($client->document ?: 'N/A')), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY(125, $yFirma + 2);
        $pdf->Cell(60, 4, self::text("FIRMA AUTORIZADA"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(125);
        $pdf->Cell(60, 4, self::text($company), 0, 1, 'C');

        $filename = "Prestamo_" . str_pad($loan->id, 5, '0', STR_PAD_LEFT) . ".pdf";
        $pdf->Output('I', $filename);
        exit;
    }

    /**
     * Genera PDF del Reporte de Préstamos por Rango de Fechas
     */
    public static function loansReportPdf(array $loans, string $start_date, string $end_date, string $status = 'all'): void {
        $pdf = new FPDF('L', 'mm', 'Letter'); // Landscape
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $cur = self::getCurrency();
        $company = self::getCompanyName();

        // Encabezado
        $pdf->SetFillColor(30, 27, 75);
        $pdf->Rect(12, 12, 255, 18, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(16, 14);
        $pdf->Cell(140, 7, self::text(strtoupper($company) . " - REPORTE DE PRÉSTAMOS"), 0, 1, 'L');

        $stText = ($status === 'all' || $status === '') ? 'Todos los Estados' : strtoupper(\LoanData::getStatusLabel($status));
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(16, 21);
        $pdf->Cell(140, 5, self::text("Período: " . date('d/m/Y', strtotime($start_date)) . " al " . date('d/m/Y', strtotime($end_date)) . "  |  Filtro Estado: {$stText}"), 0, 0, 'L');

        $pdf->SetXY(180, 16);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(80, 5, self::text("Generado: " . date('d/m/Y H:i:s')), 0, 1, 'R');

        $pdf->Ln(8);

        // Cabecera de la tabla
        $pdf->SetFillColor(99, 102, 241);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);

        $pdf->Cell(14, 7, self::text("Folio"), 1, 0, 'C', true);
        $pdf->Cell(20, 7, self::text("Fecha"), 1, 0, 'C', true);
        $pdf->Cell(52, 7, self::text("Cliente"), 1, 0, 'L', true);
        $pdf->Cell(38, 7, self::text("Producto"), 1, 0, 'L', true);
        $pdf->Cell(26, 7, self::text("Capital"), 1, 0, 'R', true);
        $pdf->Cell(16, 7, self::text("Tasa"), 1, 0, 'C', true);
        $pdf->Cell(24, 7, self::text("Plazo"), 1, 0, 'C', true);
        $pdf->Cell(28, 7, self::text("Total a Pagar"), 1, 0, 'R', true);
        $pdf->Cell(22, 7, self::text("Cuota"), 1, 0, 'R', true);
        $pdf->Cell(15, 7, self::text("Estado"), 1, 1, 'C', true);

        // Filas
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(40, 40, 40);
        $fill = false;

        $totCapital = 0;
        $totTotal = 0;

        foreach ($loans as $l) {
            $totCapital += (float)$l->amount;
            $totTotal += (float)$l->total_amount;

            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);

            $freq = \LoanData::getFrequencyLabel($l->frequency);
            $st = \LoanData::getStatusLabel($l->status);

            $pdf->Cell(14, 6, "#" . $l->id, 1, 0, 'C', true);
            $pdf->Cell(20, 6, date('d/m/Y', strtotime($l->created_at)), 1, 0, 'C', true);
            $pdf->Cell(52, 6, self::text(substr($l->client_name . " " . $l->client_lastname, 0, 28)), 1, 0, 'L', true);
            $pdf->Cell(38, 6, self::text(substr($l->product_name ?: 'General', 0, 20)), 1, 0, 'L', true);
            $pdf->Cell(26, 6, "{$cur} " . number_format($l->amount, 2), 1, 0, 'R', true);
            $pdf->Cell(16, 6, number_format($l->interest_rate, 1) . "%", 1, 0, 'C', true);
            $pdf->Cell(24, 6, self::text("{$l->term} c. ({$freq})"), 1, 0, 'C', true);
            $pdf->Cell(28, 6, "{$cur} " . number_format($l->total_amount, 2), 1, 0, 'R', true);
            $pdf->Cell(22, 6, "{$cur} " . number_format($l->installment_amount, 2), 1, 0, 'R', true);

            // Color del estado
            if ($l->status === 'active') {
                $pdf->SetTextColor(4, 120, 87);
            } elseif ($l->status === 'overdue') {
                $pdf->SetTextColor(220, 38, 38);
            } elseif ($l->status === 'paid') {
                $pdf->SetTextColor(37, 99, 235);
            } else {
                $pdf->SetTextColor(100, 116, 139);
            }
            $pdf->Cell(15, 6, self::text(substr($st, 0, 8)), 1, 1, 'C', true);
            $pdf->SetTextColor(40, 40, 40);

            $fill = !$fill;
        }

        // Totales al pie
        $pdf->SetFillColor(224, 231, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(124, 7, self::text("TOTALES (" . count($loans) . " préstamos encontrados):"), 1, 0, 'R', true);
        $pdf->Cell(26, 7, "{$cur} " . number_format($totCapital, 2), 1, 0, 'R', true);
        $pdf->Cell(40, 7, "", 1, 0, 'C', true);
        $pdf->Cell(28, 7, "{$cur} " . number_format($totTotal, 2), 1, 0, 'R', true);
        $pdf->Cell(37, 7, "", 1, 1, 'C', true);

        $filename = "Reporte_Prestamos_" . date('Ymd_His') . ".pdf";
        $pdf->Output('I', $filename);
        exit;
    }

    /**
     * Genera PDF del Reporte de Pagos por Rango de Fechas
     */
    public static function paymentsReportPdf(array $payments, string $start_date, string $end_date, string $methodName = ''): void {
        $pdf = new FPDF('P', 'mm', 'Letter'); // Portrait
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        $cur = self::getCurrency();
        $company = self::getCompanyName();

        // Encabezado
        $pdf->SetFillColor(30, 27, 75);
        $pdf->Rect(14, 14, 188, 18, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetXY(18, 16);
        $pdf->Cell(120, 7, self::text(strtoupper($company) . " - REPORTE DE PAGOS"), 0, 1, 'L');

        $metText = $methodName ? "Método: {$methodName}" : "Todos los Métodos";
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(18, 23);
        $pdf->Cell(120, 5, self::text("Período: " . date('d/m/Y', strtotime($start_date)) . " al " . date('d/m/Y', strtotime($end_date)) . "  |  {$metText}"), 0, 0, 'L');

        $pdf->SetXY(130, 18);
        $pdf->Cell(68, 5, self::text("Generado: " . date('d/m/Y H:i:s')), 0, 1, 'R');

        $pdf->Ln(8);

        // Cabecera de la tabla
        $pdf->SetFillColor(99, 102, 241);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);

        $pdf->Cell(15, 7, self::text("# Pago"), 1, 0, 'C', true);
        $pdf->Cell(28, 7, self::text("Fecha y Hora"), 1, 0, 'C', true);
        $pdf->Cell(18, 7, self::text("Préstamo"), 1, 0, 'C', true);
        $pdf->Cell(55, 7, self::text("Cliente"), 1, 0, 'L', true);
        $pdf->Cell(34, 7, self::text("Método"), 1, 0, 'L', true);
        $pdf->Cell(38, 7, self::text("Monto Pagado"), 1, 1, 'R', true);

        // Filas
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(40, 40, 40);
        $fill = false;

        $totMonto = 0;

        foreach ($payments as $p) {
            $totMonto += (float)$p->amount;

            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);

            $pdf->Cell(15, 6, "#" . $p->id, 1, 0, 'C', true);
            $pdf->Cell(28, 6, date('d/m/Y H:i', strtotime($p->created_at)), 1, 0, 'C', true);
            $pdf->Cell(18, 6, "#" . $p->loan_id, 1, 0, 'C', true);
            $pdf->Cell(55, 6, self::text(substr($p->client_name . " " . $p->client_lastname, 0, 30)), 1, 0, 'L', true);
            $pdf->Cell(34, 6, self::text(substr($p->payment_method_name ?: 'Efectivo', 0, 18)), 1, 0, 'L', true);
            $pdf->Cell(38, 6, "{$cur} " . number_format($p->amount, 2), 1, 1, 'R', true);

            $fill = !$fill;
        }

        // Totales al pie
        $pdf->SetFillColor(224, 231, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(150, 7, self::text("TOTAL RECAUDADO (" . count($payments) . " pagos):"), 1, 0, 'R', true);
        $pdf->Cell(38, 7, "{$cur} " . number_format($totMonto, 2), 1, 1, 'R', true);

        $filename = "Reporte_Pagos_" . date('Ymd_His') . ".pdf";
        $pdf->Output('I', $filename);
        exit;
    }
}
