<?php
/**
 * ASCA Selecção - Reports API (PDF / CSV generation)
 * Uses DomPDF for PDF and native PHP for CSV
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = Database::getInstance();
$report  = $_GET['report'] ?? '';
$cycleId = (int)($_GET['cycle_id'] ?? 0);

$cycle = $db->fetch("SELECT * FROM cycles WHERE id = ?", [$cycleId]);
if (!$cycle) { die('Ciclo não encontrado.'); }

switch ($report) {
    // ── CSV EXPORTS ──────────────────────
    case 'csv_members':
        requireRole(ROLE_ADMIN, ROLE_USER);
        $members = $db->fetchAll(
            "SELECT m.full_name, m.phone, m.email, m.id_number, m.join_date, m.status
             FROM members m JOIN member_cycles mc ON m.id = mc.member_id
             WHERE mc.cycle_id = ? ORDER BY m.full_name", [$cycleId]
        );
        outputCSV('membros_' . date('Ymd') . '.csv',
            ['Nome', 'Telefone', 'Email', 'BI/NUIT', 'Data Adesão', 'Status'],
            $members
        );
        break;

    case 'csv_contributions':
        requireRole(ROLE_ADMIN, ROLE_USER);
        $rows = $db->fetchAll(
            "SELECT m.full_name, DATE_FORMAT(c.reference_month, '%Y-%m') as mes, c.amount, c.paid_date, c.is_late, c.late_fee, c.payment_method
             FROM contributions c JOIN members m ON c.member_id = m.id
             WHERE c.cycle_id = ? ORDER BY c.paid_date DESC", [$cycleId]
        );
        outputCSV('contribuicoes_' . date('Ymd') . '.csv',
            ['Membro', 'Mês Ref', 'Valor', 'Data Pgto', 'Atraso', 'Multa', 'Método'],
            $rows
        );
        break;

    case 'csv_loans':
        requireRole(ROLE_ADMIN, ROLE_USER);
        $rows = $db->fetchAll(
            "SELECT m.full_name, l.amount, l.disbursement_date, l.due_date, l.status
             FROM loans l JOIN members m ON l.member_id = m.id
             WHERE l.cycle_id = ? ORDER BY l.disbursement_date DESC", [$cycleId]
        );
        outputCSV('emprestimos_' . date('Ymd') . '.csv',
            ['Membro', 'Valor', 'Data Desembolso', 'Vencimento', 'Status'],
            $rows
        );
        break;

    // ── HTML/PDF Reports ──────────────────
    case 'member_extract':
    case 'monthly_report':
    case 'loans_status':
    case 'contributions_map':
    case 'balance':
        requireRole(ROLE_ADMIN, ROLE_USER);
        generateHTMLReport($report, $cycleId, $db, $cycle);
        break;

    // ── WhatsApp Summary (JSON) ──────────
    case 'whatsapp_summary':
        requireRole(ROLE_ADMIN, ROLE_USER);
        header('Content-Type: application/json; charset=utf-8');
        $memberId = (int)($_GET['member_id'] ?? 0);
        $msgType  = $_GET['msg_type'] ?? 'account_summary';
        
        $member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
        if (!$member) { echo json_encode(['success' => false, 'message' => 'Membro não encontrado']); exit; }
        
        $totalContrib = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId])['t'];
        $totalLateFees = (float)$db->fetch("SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ? AND is_late = 1", [$memberId, $cycleId])['t'];
        
        $activeLoans = $db->fetchAll("SELECT l.amount, l.due_date, l.status,
            (SELECT COALESCE(SUM(lr.amount),0) FROM loan_repayments lr WHERE lr.loan_id = l.id) as repaid
            FROM loans l WHERE l.member_id = ? AND l.cycle_id = ? AND l.status IN ('active','overdue')", [$memberId, $cycleId]);
        
        $totalOwed = 0;
        foreach ($activeLoans as $al) $totalOwed += ((float)$al['amount'] - (float)$al['repaid']);
        
        $pendingInterest = (float)$db->fetch("SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE member_id = ? AND cycle_id = ? AND status = 'pending'", [$memberId, $cycleId])['t'];
        
        $phone = preg_replace('/[^0-9]/', '', $member['phone'] ?? '');
        if (strlen($phone) === 9) $phone = '258' . $phone; // Mozambique prefix
        
        $cycleName = $cycle['name'];
        $memberName = $member['full_name'];
        $fmt = function($v) { return number_format($v, 2, ',', '.') . ' MT'; };
        
        switch ($msgType) {
            case 'payment_reminder':
                $dueInfo = '';
                foreach ($activeLoans as $al) {
                    $remaining = (float)$al['amount'] - (float)$al['repaid'];
                    $dueInfo .= "\n  • " . $fmt($remaining) . " (vence " . date('d/m/Y', strtotime($al['due_date'])) . ")";
                }
                $message = "📋 *ASCA Selecção — Lembrete de Pagamento*\n\n"
                    . "Olá *{$memberName}*,\n\n"
                    . "Gostaríamos de lembrar que tem obrigações pendentes no ciclo *{$cycleName}*:\n"
                    . ($totalOwed > 0 ? "\n💰 *Empréstimos pendentes:*{$dueInfo}\n" : "")
                    . ($pendingInterest > 0 ? "\n📈 *Juros pendentes:* " . $fmt($pendingInterest) . "\n" : "")
                    . "\nAgradecemos a regularização atempada.\n\n_Gerado automaticamente pelo sistema ASCA Selecção._";
                break;
                
            case 'debt_balance':
                $message = "📊 *ASCA Selecção — Saldo Devedor*\n\n"
                    . "Membro: *{$memberName}*\n"
                    . "Ciclo: *{$cycleName}*\n\n"
                    . "💰 Empréstimos em aberto: *" . $fmt($totalOwed) . "*\n"
                    . "📈 Juros pendentes: *" . $fmt($pendingInterest) . "*\n"
                    . "──────────────\n"
                    . "🔴 *Total em dívida: " . $fmt($totalOwed + $pendingInterest) . "*\n\n"
                    . "_Gerado em " . date('d/m/Y H:i') . "_";
                break;
                
            default: // account_summary
                // Proportional share calculation for reports
                $groupTotals = $db->fetch(
                    "SELECT 
                        (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE cycle_id = ?) +
                        (SELECT COALESCE(SUM(amount), 0) FROM joias WHERE cycle_id = ? AND status = 'paid') as total_savings,
                        (SELECT COALESCE(SUM(interest_amount), 0) FROM loan_interest WHERE cycle_id = ? AND status = 'paid') as total_interest",
                    [$cycleId, $cycleId, $cycleId]
                );
                $gSavings = (float)($groupTotals['total_savings'] ?: 1);
                $gInterest = (float)$groupTotals['total_interest'];
                $myInterestShare = ($totalContrib / $gSavings) * $gInterest;
                $tolerance = (float)($cycle['loan_tolerance_margin'] ?? 0);
                $loanCapacity = $totalContrib + $myInterestShare + $tolerance;

                $message = "📋 *ASCA Selecção — Resumo de Conta*\n\n"
                    . "Membro: *{$memberName}*\n"
                    . "Ciclo: *{$cycleName}*\n\n"
                    . "✅ Total contribuído: *" . $fmt($totalContrib) . "*\n"
                    . "📈 Rendimentos (Juros): *" . $fmt($myInterestShare) . "*\n"
                    . "🚀 *Capacidade de Empréstimo: " . $fmt($loanCapacity) . "*\n"
                    . "⚠️ Multas de atraso: *" . $fmt($totalLateFees) . "*\n"
                    . "💰 Dívida activa: *" . $fmt($totalOwed) . "*\n"
                    . "📊 Juros pendentes: *" . $fmt($pendingInterest) . "*\n\n"
                    . "_Gerado em " . date('d/m/Y H:i') . "_";
                break;
        }
        
        echo json_encode([
            'success' => true,
            'phone' => $phone,
            'member_name' => $memberName,
            'message' => $message,
            'data' => [
                'total_contributed' => $totalContrib,
                'total_late_fees' => $totalLateFees,
                'total_owed' => $totalOwed,
                'pending_interest' => $pendingInterest,
                'loan_capacity' => $loanCapacity ?? 0
            ]
        ]);
        exit;

    default:
        die('Relatório inválido.');
}

/**
 * Output CSV download
 */
function outputCSV(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fprintf($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($output, array_values($row), ';');
    }
    fclose($output);
    exit;
}

/**
 * Generate HTML-based printable report (fallback when DomPDF is not installed)
 */
function generateHTMLReport(string $type, int $cycleId, Database $db, array $cycle): void {
    $title = [
        'member_extract' => 'Extracto de Membro',
        'monthly_report' => 'Relatório Mensal',
        'loans_status'   => 'Estado de Empréstimos',
        'contributions_map' => 'Mapa de Contribuições',
        'balance'        => 'Balanço Geral',
    ][$type] ?? 'Relatório';

    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title><?= APP_NAME ?> — <?= $title ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Arial', sans-serif; font-size: 12px; color: #333; padding: 2rem; }
            .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1a73e8; padding-bottom: .75rem; margin-bottom: 1.5rem; }
            .header h1 { font-size: 18px; color: #1a73e8; }
            .header .meta { text-align: right; font-size: 11px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
            th, td { padding: 6px 10px; text-align: left; border: 1px solid #ddd; }
            th { background: #f0f2f5; font-weight: 600; font-size: 11px; text-transform: uppercase; }
            .total-row { font-weight: bold; background: #e8f0fe; }
            .text-right { text-align: right; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
            .badge-success { background: #d1fae5; color: #065f46; }
            .badge-danger { background: #fee2e2; color: #991b1b; }
            .badge-warning { background: #fef3c7; color: #92400e; }
            .section { margin-top: 2rem; }
            .section h2 { font-size: 14px; color: #1e293b; margin-bottom: .5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: .25rem; }
            .summary-box { display: inline-block; padding: .5rem 1rem; margin: .25rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
            .summary-box .label { font-size: 10px; color: #64748b; }
            .summary-box .value { font-size: 16px; font-weight: 700; }
            @media print {
                body { padding: 1cm; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom:1rem;">
            <button onclick="window.print()" style="padding:.5rem 1rem;background:#1a73e8;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                🖨️ Imprimir / Guardar PDF
            </button>
        </div>
        <div class="header">
            <div>
                <h1><?= APP_NAME ?></h1>
                <div style="font-size:14px;margin-top:.25rem;"><?= $title ?></div>
            </div>
            <div class="meta">
                Ciclo: <?= sanitize($cycle['name']) ?><br>
                Gerado em: <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    <?php

    switch ($type) {
        case 'member_extract':
            $memberId = (int)($_GET['member_id'] ?? 0);
            $member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
            if (!$member) { echo '<p>Membro não encontrado.</p>'; break; }
            
            echo "<div class='section'><h2>Membro: {$member['full_name']}</h2>";
            echo "<p>Telefone: {$member['phone']} | Email: {$member['email']} | Adesão: " . formatDate($member['join_date']) . "</p></div>";

            // Contributions
            $contribs = $db->fetchAll(
                "SELECT * FROM contributions WHERE member_id = ? AND cycle_id = ? ORDER BY reference_month",
                [$memberId, $cycleId]
            );
            echo "<div class='section'><h2>Contribuições</h2><table><thead><tr><th>Mês</th><th>Valor</th><th>Data Pgto</th><th>Multa</th><th>Status</th></tr></thead><tbody>";
            $totalC = 0; $totalF = 0;
            foreach ($contribs as $c) {
                $totalC += (float)$c['amount']; $totalF += (float)$c['late_fee'];
                $status = $c['is_late'] ? '<span class="badge badge-danger">Atraso</span>' : '<span class="badge badge-success">OK</span>';
                echo "<tr><td>" . formatDate($c['reference_month']) . "</td><td class='text-right'>" . formatMoney($c['amount']) . "</td><td>" . formatDate($c['paid_date']) . "</td><td class='text-right'>" . formatMoney($c['late_fee']) . "</td><td>{$status}</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL</td><td class='text-right'>" . formatMoney($totalC) . "</td><td></td><td class='text-right'>" . formatMoney($totalF) . "</td><td></td></tr>";
            echo "</tbody></table></div>";

            // Loans
            $loans = $db->fetchAll("SELECT * FROM loans WHERE member_id = ? AND cycle_id = ? ORDER BY disbursement_date", [$memberId, $cycleId]);
            echo "<div class='section'><h2>Empréstimos</h2><table><thead><tr><th>Valor</th><th>Desembolso</th><th>Vencimento</th><th>Status</th></tr></thead><tbody>";
            foreach ($loans as $l) {
                $sBadge = ['active' => 'badge-warning', 'paid' => 'badge-success', 'overdue' => 'badge-danger'][$l['status']] ?? '';
                echo "<tr><td class='text-right'>" . formatMoney($l['amount']) . "</td><td>" . formatDate($l['disbursement_date']) . "</td><td>" . formatDate($l['due_date']) . "</td><td><span class='badge {$sBadge}'>" . ucfirst($l['status']) . "</span></td></tr>";
            }
            echo "</tbody></table></div>";
            break;

        case 'loans_status':
            $statusFilter = $_GET['status'] ?? '';
            $statusClause = '';
            $params = [$cycleId];
            if ($statusFilter && in_array($statusFilter, ['active', 'paid', 'overdue'])) {
                $statusClause = " AND l.status = ?";
                $params[] = $statusFilter;
            }
            $loans = $db->fetchAll(
                "SELECT l.*, m.full_name, 
                        (SELECT COALESCE(SUM(lr.amount),0) FROM loan_repayments lr WHERE lr.loan_id = l.id) as repaid
                 FROM loans l JOIN members m ON l.member_id = m.id WHERE l.cycle_id = ?{$statusClause} ORDER BY l.status, m.full_name", $params
            );
            $filterLabel = $statusFilter ? ' (' . ucfirst($statusFilter) . ')' : '';
            echo "<div class='section'><h2>Estado de Empréstimos{$filterLabel}</h2></div>";
            echo "<table><thead><tr><th>Membro</th><th>Valor</th><th>Desembolso</th><th>Vencimento</th><th>Reembolsado</th><th>Saldo</th><th>Status</th></tr></thead><tbody>";
            $tLoaned = 0; $tRepaid = 0;
            foreach ($loans as $l) {
                $remaining = (float)$l['amount'] - (float)$l['repaid'];
                $tLoaned += (float)$l['amount']; $tRepaid += (float)$l['repaid'];
                $sBadge = ['active' => 'badge-warning', 'paid' => 'badge-success', 'overdue' => 'badge-danger'][$l['status']] ?? '';
                $statusLabel = ['active' => 'Activo', 'paid' => 'Pago', 'overdue' => 'Em Atraso'][$l['status']] ?? $l['status'];
                echo "<tr><td>{$l['full_name']}</td><td class='text-right'>" . formatMoney($l['amount']) . "</td><td>" . formatDate($l['disbursement_date']) . "</td><td>" . formatDate($l['due_date']) . "</td><td class='text-right'>" . formatMoney($l['repaid']) . "</td><td class='text-right'>" . formatMoney($remaining) . "</td><td><span class='badge {$sBadge}'>{$statusLabel}</span></td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($loans) . " empréstimos)</td><td class='text-right'>" . formatMoney($tLoaned) . "</td><td></td><td></td><td class='text-right'>" . formatMoney($tRepaid) . "</td><td class='text-right'>" . formatMoney($tLoaned - $tRepaid) . "</td><td></td></tr>";
            echo "</tbody></table>";
            break;

        case 'balance':
            $totalContrib = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE cycle_id = ?", [$cycleId])['t'];
            $totalLateFees = $db->fetch("SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE cycle_id = ? AND is_late = 1", [$cycleId])['t'];
            $totalJoias = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM joias WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
            $totalInterest = $db->fetch("SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
            $totalLoaned = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM loans WHERE cycle_id = ?", [$cycleId])['t'];
            $totalRepaid = $db->fetch("SELECT COALESCE(SUM(lr.amount),0) as t FROM loan_repayments lr JOIN loans l ON lr.loan_id = l.id WHERE l.cycle_id = ?", [$cycleId])['t'];

            $totalIn = (float)$totalContrib + (float)$totalLateFees + (float)$totalJoias + (float)$totalInterest;
            $totalOut = (float)$totalLoaned;
            $balance = $totalIn - $totalOut + (float)$totalRepaid;

            echo "<div style='margin: 1rem 0;'>";
            echo "<div class='summary-box'><div class='label'>Contribuições</div><div class='value'>" . formatMoney($totalContrib) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Jóias</div><div class='value'>" . formatMoney($totalJoias) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Multas</div><div class='value'>" . formatMoney($totalLateFees) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Juros</div><div class='value'>" . formatMoney($totalInterest) . "</div></div>";
            echo "</div>";

            echo "<table><thead><tr><th>Descrição</th><th class='text-right'>Valor</th></tr></thead><tbody>";
            echo "<tr><td>Total Contribuições</td><td class='text-right'>" . formatMoney($totalContrib) . "</td></tr>";
            echo "<tr><td>Total Jóias</td><td class='text-right'>" . formatMoney($totalJoias) . "</td></tr>";
            echo "<tr><td>Total Multas de Atraso</td><td class='text-right'>" . formatMoney($totalLateFees) . "</td></tr>";
            echo "<tr><td>Total Juros de Empréstimos</td><td class='text-right'>" . formatMoney($totalInterest) . "</td></tr>";
            echo "<tr class='total-row'><td>TOTAL ENTRADAS</td><td class='text-right'>" . formatMoney($totalIn) . "</td></tr>";
            echo "<tr><td>Total Emprestado</td><td class='text-right'>-" . formatMoney($totalLoaned) . "</td></tr>";
            echo "<tr><td>Total Reembolsado</td><td class='text-right'>+" . formatMoney($totalRepaid) . "</td></tr>";
            echo "<tr class='total-row'><td>SALDO ACTUAL</td><td class='text-right'>" . formatMoney($balance) . "</td></tr>";
            echo "</tbody></table>";
            break;

        case 'monthly_report':
            $month = $_GET['month'] ?? date('Y-m');
            $monthStart = $month . '-01';
            $monthEnd   = date('Y-m-t', strtotime($monthStart));
            $monthLabel = strftime('%B %Y', strtotime($monthStart));
            // Use IntlDateFormatter for Portuguese month names
            if (class_exists('IntlDateFormatter')) {
                $df = new IntlDateFormatter('pt_MZ', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
                $df->setPattern('MMMM yyyy');
                $monthLabel = ucfirst($df->format(new DateTime($monthStart)));
            }

            echo "<div class='section'><h2>Relatório Mensal — {$monthLabel}</h2></div>";

            // 1. Contributions
            $contribs = $db->fetchAll(
                "SELECT m.full_name, c.amount, c.paid_date, c.is_late, c.late_fee, c.payment_method
                 FROM contributions c JOIN members m ON c.member_id = m.id
                 WHERE c.cycle_id = ? AND c.reference_month = ?
                 ORDER BY m.full_name",
                [$cycleId, $monthStart]
            );
            $totalC = 0; $totalF = 0;
            echo "<div class='section'><h2>💰 Contribuições Recebidas</h2>";
            echo "<table><thead><tr><th>Membro</th><th>Valor</th><th>Data Pgto</th><th>Multa</th><th>Método</th><th>Status</th></tr></thead><tbody>";
            foreach ($contribs as $c) {
                $totalC += (float)$c['amount']; $totalF += (float)$c['late_fee'];
                $status = $c['is_late'] ? '<span class="badge badge-danger">Atraso</span>' : '<span class="badge badge-success">OK</span>';
                echo "<tr><td>{$c['full_name']}</td><td class='text-right'>" . formatMoney($c['amount']) . "</td><td>" . formatDate($c['paid_date']) . "</td><td class='text-right'>" . formatMoney($c['late_fee']) . "</td><td>" . ($c['payment_method'] ?? '-') . "</td><td>{$status}</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($contribs) . ")</td><td class='text-right'>" . formatMoney($totalC) . "</td><td></td><td class='text-right'>" . formatMoney($totalF) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // 2. Loans Disbursed
            $loans = $db->fetchAll(
                "SELECT m.full_name, l.amount, l.disbursement_date, l.due_date
                 FROM loans l JOIN members m ON l.member_id = m.id
                 WHERE l.cycle_id = ? AND l.disbursement_date BETWEEN ? AND ?
                 ORDER BY l.disbursement_date",
                [$cycleId, $monthStart, $monthEnd]
            );
            $totalL = 0;
            echo "<div class='section'><h2>🏦 Empréstimos Desembolsados</h2>";
            echo "<table><thead><tr><th>Membro</th><th>Valor</th><th>Data Desembolso</th><th>Vencimento</th></tr></thead><tbody>";
            foreach ($loans as $l) {
                $totalL += (float)$l['amount'];
                echo "<tr><td>{$l['full_name']}</td><td class='text-right'>" . formatMoney($l['amount']) . "</td><td>" . formatDate($l['disbursement_date']) . "</td><td>" . formatDate($l['due_date']) . "</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($loans) . ")</td><td class='text-right'>" . formatMoney($totalL) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // 3. Repayments Received
            $repayments = $db->fetchAll(
                "SELECT m.full_name, lr.amount, lr.paid_date, l.amount as loan_amount
                 FROM loan_repayments lr
                 JOIN loans l ON lr.loan_id = l.id
                 JOIN members m ON l.member_id = m.id
                 WHERE l.cycle_id = ? AND lr.paid_date BETWEEN ? AND ?
                 ORDER BY lr.paid_date",
                [$cycleId, $monthStart, $monthEnd]
            );
            $totalR = 0;
            echo "<div class='section'><h2>✅ Reembolsos Recebidos</h2>";
            echo "<table><thead><tr><th>Membro</th><th>Valor Pago</th><th>Data</th><th>Empréstimo Original</th></tr></thead><tbody>";
            foreach ($repayments as $r) {
                $totalR += (float)$r['amount'];
                echo "<tr><td>{$r['full_name']}</td><td class='text-right'>" . formatMoney($r['amount']) . "</td><td>" . formatDate($r['paid_date']) . "</td><td class='text-right'>" . formatMoney($r['loan_amount']) . "</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($repayments) . ")</td><td class='text-right'>" . formatMoney($totalR) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // 4. Interest Charged
            $interest = $db->fetchAll(
                "SELECT m.full_name, li.interest_amount, li.reference_month, li.status
                 FROM loan_interest li
                 JOIN members m ON li.member_id = m.id
                 WHERE li.cycle_id = ? AND li.reference_month = ?
                 ORDER BY li.reference_month",
                [$cycleId, $monthStart]
            );
            $totalI = 0;
            echo "<div class='section'><h2>📈 Juros Cobrados</h2>";
            echo "<table><thead><tr><th>Membro</th><th>Valor Juros</th><th>Mês Ref</th><th>Status</th></tr></thead><tbody>";
            foreach ($interest as $i) {
                $totalI += (float)$i['interest_amount'];
                $sBadge = $i['status'] === 'paid' ? 'badge-success' : 'badge-warning';
                $sLabel = $i['status'] === 'paid' ? 'Pago' : 'Pendente';
                echo "<tr><td>{$i['full_name']}</td><td class='text-right'>" . formatMoney($i['interest_amount']) . "</td><td>" . formatDate($i['reference_month']) . "</td><td><span class='badge {$sBadge}'>{$sLabel}</span></td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($interest) . ")</td><td class='text-right'>" . formatMoney($totalI) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // Monthly Summary
            echo "<div class='section'><h2>📊 Resumo do Mês</h2>";
            echo "<div style='margin: .5rem 0;'>";
            echo "<div class='summary-box'><div class='label'>Contribuições</div><div class='value' style='color:#0d9488;'>+" . formatMoney($totalC) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Multas</div><div class='value' style='color:#f59e0b;'>+" . formatMoney($totalF) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Juros</div><div class='value' style='color:#0ea5e9;'>+" . formatMoney($totalI) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Reembolsos</div><div class='value' style='color:#0d9488;'>+" . formatMoney($totalR) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Empréstimos</div><div class='value' style='color:#dc2626;'>-" . formatMoney($totalL) . "</div></div>";
            $netFlow = $totalC + $totalF + $totalI + $totalR - $totalL;
            echo "<div class='summary-box' style='border-color:#1a73e8;'><div class='label'>Fluxo Líquido</div><div class='value' style='color:" . ($netFlow >= 0 ? '#0d9488' : '#dc2626') . ";'>" . formatMoney($netFlow) . "</div></div>";
            echo "</div></div>";
            break;

        case 'contributions_map':
            // Get all members in this cycle
            $members = $db->fetchAll(
                "SELECT m.id, m.full_name FROM members m
                 JOIN member_cycles mc ON m.id = mc.member_id
                 WHERE mc.cycle_id = ? AND mc.status = 'active'
                 ORDER BY m.full_name", [$cycleId]
            );

            // Get all contributions grouped by member and month
            $contribs = $db->fetchAll(
                "SELECT member_id, DATE_FORMAT(reference_month, '%Y-%m') as ref_month, SUM(amount) as total
                 FROM contributions WHERE cycle_id = ?
                 GROUP BY member_id, ref_month",
                [$cycleId]
            );

            // Build lookup: member_id -> month -> amount
            $lookup = [];
            $allMonths = [];
            foreach ($contribs as $c) {
                $lookup[$c['member_id']][$c['ref_month']] = (float)$c['total'];
                $allMonths[$c['ref_month']] = true;
            }
            ksort($allMonths);
            $months = array_keys($allMonths);

            // Month labels (short Portuguese)
            $monthLabels = [];
            foreach ($months as $m) {
                $dt = new DateTime($m . '-01');
                $monthLabels[$m] = $dt->format('M/y');
            }

            echo "<div class='section'><h2>Mapa de Contribuições — " . sanitize($cycle['name']) . "</h2></div>";
            echo "<div style='overflow-x:auto;'>";
            echo "<table><thead><tr><th>Membro</th>";
            foreach ($months as $m) {
                echo "<th class='text-right' style='min-width:80px;'>{$monthLabels[$m]}</th>";
            }
            echo "<th class='text-right' style='font-weight:700;'>TOTAL</th></tr></thead><tbody>";

            $colTotals = array_fill_keys($months, 0);
            $grandTotal = 0;

            foreach ($members as $member) {
                $rowTotal = 0;
                echo "<tr><td style='white-space:nowrap;'>{$member['full_name']}</td>";
                foreach ($months as $m) {
                    $val = $lookup[$member['id']][$m] ?? 0;
                    $rowTotal += $val;
                    $colTotals[$m] += $val;
                    if ($val > 0) {
                        echo "<td class='text-right'>" . formatMoney($val) . "</td>";
                    } else {
                        echo "<td class='text-right' style='color:#dc2626;'>—</td>";
                    }
                }
                $grandTotal += $rowTotal;
                echo "<td class='text-right' style='font-weight:600;'>" . formatMoney($rowTotal) . "</td></tr>";
            }

            echo "<tr class='total-row'><td>TOTAL</td>";
            foreach ($months as $m) {
                echo "<td class='text-right'>" . formatMoney($colTotals[$m]) . "</td>";
            }
            echo "<td class='text-right'>" . formatMoney($grandTotal) . "</td></tr>";
            echo "</tbody></table></div>";
            break;
    }

    echo '</body></html>';
    exit;
}
