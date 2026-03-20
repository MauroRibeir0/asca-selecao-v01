<?php
/**
 * ASCA Selecção - Reports API (HTML/Print & CSV generation)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db      = Database::getInstance();
$report  = $_GET['report'] ?? '';
$cycleId = (int)($_GET['cycle_id'] ?? 0);

$cycle = $db->fetch("SELECT * FROM cycles WHERE id = ?", [$cycleId]);
if (!$cycle) { die('Ciclo não encontrado.'); }

switch ($report) {

    // ── CSV EXPORTS ───────────────────────────────────────────────────────────

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
            "SELECT m.full_name, DATE_FORMAT(c.reference_month, '%Y-%m') as mes,
                    c.amount, c.paid_date, c.is_late, c.late_fee, c.payment_method
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
            "SELECT m.full_name, l.amount,
                    COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id), 0) AS reembolsado,
                    l.amount - COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id), 0) AS saldo,
                    l.disbursement_date, l.due_date, l.status
             FROM loans l JOIN members m ON l.member_id = m.id
             WHERE l.cycle_id = ? ORDER BY l.disbursement_date DESC", [$cycleId]
        );
        outputCSV('emprestimos_' . date('Ymd') . '.csv',
            ['Membro', 'Valor', 'Reembolsado', 'Saldo', 'Data Desembolso', 'Vencimento', 'Status'],
            $rows
        );
        break;

    case 'csv_joias':
        requireRole(ROLE_ADMIN, ROLE_USER);
        $rows = $db->fetchAll(
            "SELECT m.full_name, j.amount, j.paid_date, j.status, j.notes
             FROM joias j JOIN members m ON j.member_id = m.id
             WHERE j.cycle_id = ? ORDER BY m.full_name", [$cycleId]
        );
        outputCSV('joias_' . date('Ymd') . '.csv',
            ['Membro', 'Valor', 'Data Pgto', 'Status', 'Notas'],
            $rows
        );
        break;

    // ── HTML / PRINT REPORTS ──────────────────────────────────────────────────

    case 'member_extract':
    case 'monthly_report':
    case 'loans_status':
    case 'contributions_map':
    case 'balance':
    case 'joia_status':
    case 'distribution_report':
    case 'eligibility_report':
        requireRole(ROLE_ADMIN, ROLE_USER);
        generateHTMLReport($report, $cycleId, $db, $cycle);
        break;

    // ── WHATSAPP SUMMARY (JSON) ───────────────────────────────────────────────

    case 'whatsapp_summary':
        requireRole(ROLE_ADMIN, ROLE_USER);
        header('Content-Type: application/json; charset=utf-8');

        $memberId = (int)($_GET['member_id'] ?? 0);
        $msgType  = $_GET['msg_type'] ?? 'account_summary';

        $member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
        if (!$member) {
            echo json_encode(['success' => false, 'message' => 'Membro não encontrado']);
            exit;
        }

        $totalContrib  = (float)$db->fetch(
            "SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ?",
            [$memberId, $cycleId]
        )['t'];
        $totalLateFees = (float)$db->fetch(
            "SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ? AND is_late = 1",
            [$memberId, $cycleId]
        )['t'];

        // Active loans with partial repayments
        $activeLoans = $db->fetchAll(
            "SELECT l.amount, l.due_date, l.status,
                    COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id), 0) as repaid
             FROM loans l WHERE l.member_id = ? AND l.cycle_id = ? AND l.status IN ('active','overdue')",
            [$memberId, $cycleId]
        );
        $totalOwed = 0;
        foreach ($activeLoans as $al) {
            $totalOwed += max(0, (float)$al['amount'] - (float)$al['repaid']);
        }

        $pendingInterest = (float)$db->fetch(
            "SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE member_id = ? AND cycle_id = ? AND status = 'pending'",
            [$memberId, $cycleId]
        )['t'];

        $phone = preg_replace('/[^0-9]/', '', $member['phone'] ?? '');
        if (strlen($phone) === 9) $phone = '258' . $phone;

        $cycleName  = $cycle['name'];
        $memberName = $member['full_name'];
        $fmt = function ($v) { return number_format((float)$v, 2, ',', '.') . ' MT'; };

        $loanCapacity = 0;

        switch ($msgType) {
            case 'payment_reminder':
                $dueInfo = '';
                foreach ($activeLoans as $al) {
                    $remaining = max(0, (float)$al['amount'] - (float)$al['repaid']);
                    if ($remaining > 0) {
                        $dueInfo .= "\n  • " . $fmt($remaining) . " (vence " . date('d/m/Y', strtotime($al['due_date'])) . ")";
                    }
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
                // Interest share uses contributions-only denominator (matches distribution business rules)
                $groupContributions = (float)$db->fetch(
                    "SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE cycle_id = ?",
                    [$cycleId]
                )['t'];
                $gInterest = (float)$db->fetch(
                    "SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'paid'",
                    [$cycleId]
                )['t'];
                $myInterestShare = ($groupContributions > 0)
                    ? round(($totalContrib / $groupContributions) * $gInterest, 2)
                    : 0.0;
                $tolerance    = (float)($cycle['loan_tolerance_margin'] ?? 0);
                // Loan capacity deducts active debt
                $loanCapacity = max(0, $totalContrib + $myInterestShare + $tolerance - $totalOwed);

                $message = "📋 *ASCA Selecção — Resumo de Conta*\n\n"
                    . "Membro: *{$memberName}*\n"
                    . "Ciclo: *{$cycleName}*\n\n"
                    . "✅ Total contribuído: *" . $fmt($totalContrib) . "*\n"
                    . "📈 Rendimentos estimados (Juros): *" . $fmt($myInterestShare) . "*\n"
                    . "⚠️  Multas de atraso: *" . $fmt($totalLateFees) . "*\n"
                    . "💰 Dívida activa: *" . $fmt($totalOwed) . "*\n"
                    . "📊 Juros pendentes: *" . $fmt($pendingInterest) . "*\n"
                    . "🚀 *Capacidade de Empréstimo: " . $fmt($loanCapacity) . "*\n\n"
                    . "_Gerado em " . date('d/m/Y H:i') . "_";
                break;
        }

        echo json_encode([
            'success'     => true,
            'phone'       => $phone,
            'member_name' => $memberName,
            'message'     => $message,
            'data'        => [
                'total_contributed' => $totalContrib,
                'total_late_fees'   => $totalLateFees,
                'total_owed'        => $totalOwed,
                'pending_interest'  => $pendingInterest,
                'loan_capacity'     => $loanCapacity,
            ],
        ]);
        exit;

    // ── BULK WHATSAPP LIST (JSON) ─────────────────────────────────────────────

    case 'whatsapp_bulk_list':
        requireRole(ROLE_ADMIN, ROLE_USER);
        header('Content-Type: application/json; charset=utf-8');

        $members = $db->fetchAll(
            "SELECT m.id, m.full_name, m.phone FROM members m
             JOIN member_cycles mc ON m.id = mc.member_id
             WHERE mc.cycle_id = ? AND mc.status = 'active'
             ORDER BY m.full_name",
            [$cycleId]
        );

        $result = [];
        foreach ($members as $m) {
            $phone = preg_replace('/[^0-9]/', '', $m['phone'] ?? '');
            if (strlen($phone) === 9) $phone = '258' . $phone;

            $totalOwed = (float)$db->fetch(
                "SELECT COALESCE(SUM(l.amount - COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id),0)),0) as t
                 FROM loans l WHERE l.member_id = ? AND l.cycle_id = ? AND l.status IN ('active','overdue')",
                [$m['id'], $cycleId]
            )['t'];
            $pendingInterest = (float)$db->fetch(
                "SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE member_id = ? AND cycle_id = ? AND status = 'pending'",
                [$m['id'], $cycleId]
            )['t'];

            $result[] = [
                'id'              => $m['id'],
                'full_name'       => $m['full_name'],
                'phone'           => $phone,
                'has_debt'        => $totalOwed > 0 || $pendingInterest > 0,
                'total_owed'      => $totalOwed,
                'pending_interest'=> $pendingInterest,
            ];
        }

        echo json_encode(['success' => true, 'members' => $result]);
        exit;

    default:
        die('Relatório inválido.');
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Output a UTF-8 CSV download (Excel-compatible with BOM).
 */
function outputCSV(string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // BOM
    fputcsv($out, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($out, array_values($row), ';');
    }
    fclose($out);
    exit;
}

/**
 * Generate a printable HTML report.
 */
function generateHTMLReport(string $type, int $cycleId, Database $db, array $cycle): void {
    $titles = [
        'member_extract'      => 'Extracto de Membro',
        'monthly_report'      => 'Relatório Mensal',
        'loans_status'        => 'Estado de Empréstimos',
        'contributions_map'   => 'Mapa de Contribuições',
        'balance'             => 'Balanço Geral',
        'joia_status'         => 'Estado das Jóias',
        'distribution_report' => 'Relatório de Distribuição',
        'eligibility_report'  => 'Relatório de Elegibilidade',
    ];
    $title = $titles[$type] ?? 'Relatório';

    // Portuguese month name arrays (used by several report types)
    $ptMonthAbbr = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    $ptMonthFull = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title><?= APP_NAME ?> — <?= $title ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 2rem; }
            .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1a73e8; padding-bottom: .75rem; margin-bottom: 1.5rem; }
            .header h1 { font-size: 18px; color: #1a73e8; }
            .header .meta { text-align: right; font-size: 11px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
            th, td { padding: 6px 10px; text-align: left; border: 1px solid #ddd; }
            th { background: #f0f2f5; font-weight: 600; font-size: 11px; text-transform: uppercase; }
            .total-row { font-weight: bold; background: #e8f0fe; }
            .text-right  { text-align: right; }
            .text-center { text-align: center; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
            .badge-success { background: #d1fae5; color: #065f46; }
            .badge-danger  { background: #fee2e2; color: #991b1b; }
            .badge-warning { background: #fef3c7; color: #92400e; }
            .badge-info    { background: #dbeafe; color: #1e40af; }
            .section { margin-top: 2rem; }
            .section h2 { font-size: 14px; color: #1e293b; margin-bottom: .5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: .25rem; }
            .kpi-grid { display: flex; flex-wrap: wrap; gap: .5rem; margin: 1rem 0; }
            .summary-box { display: inline-block; padding: .5rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
            .summary-box .label { font-size: 10px; color: #64748b; }
            .summary-box .value { font-size: 15px; font-weight: 700; }
            @media print { body { padding: 1cm; } .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom:1rem;">
            <button onclick="window.print()" style="padding:.5rem 1.25rem;background:#1a73e8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
                Imprimir / Guardar PDF
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

        // ── MEMBER EXTRACT ───────────────────────────────────────────────────
        case 'member_extract':
            $memberId = (int)($_GET['member_id'] ?? 0);
            $member   = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
            if (!$member) { echo '<p>Membro não encontrado.</p>'; break; }

            echo "<div class='section'><h2>Membro: " . sanitize($member['full_name']) . "</h2>";
            echo "<p>Tel: " . ($member['phone'] ?: '—') . " &nbsp;|&nbsp; Email: " . ($member['email'] ?: '—')
                . " &nbsp;|&nbsp; Adesão: " . formatDate($member['join_date']) . "</p></div>";

            // Contributions
            $contribs = $db->fetchAll(
                "SELECT * FROM contributions WHERE member_id = ? AND cycle_id = ? ORDER BY reference_month",
                [$memberId, $cycleId]
            );
            echo "<div class='section'><h2>Contribuições</h2>
                  <table><thead><tr><th>Mês</th><th class='text-right'>Valor</th><th>Data Pgto</th><th class='text-right'>Multa</th><th>Status</th></tr></thead><tbody>";
            $totalC = 0; $totalF = 0;
            foreach ($contribs as $c) {
                $totalC += (float)$c['amount']; $totalF += (float)$c['late_fee'];
                $badge = $c['is_late'] ? "<span class='badge badge-danger'>Atraso</span>" : "<span class='badge badge-success'>OK</span>";
                echo "<tr><td>" . formatDate($c['reference_month']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($c['amount']) . "</td>"
                    . "<td>" . formatDate($c['paid_date']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($c['late_fee']) . "</td>"
                    . "<td>$badge</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL</td><td class='text-right'>" . formatMoney($totalC) . "</td>"
                . "<td></td><td class='text-right'>" . formatMoney($totalF) . "</td><td></td></tr>";
            echo "</tbody></table></div>";

            // Loans (with repayment balance)
            $loans = $db->fetchAll(
                "SELECT l.*,
                        COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id), 0) AS repaid
                 FROM loans l WHERE l.member_id = ? AND l.cycle_id = ? ORDER BY l.disbursement_date",
                [$memberId, $cycleId]
            );
            echo "<div class='section'><h2>Empréstimos</h2>
                  <table><thead><tr>
                    <th class='text-right'>Valor</th><th>Desembolso</th><th>Vencimento</th>
                    <th class='text-right'>Reembolsado</th><th class='text-right'>Saldo</th><th>Status</th>
                  </tr></thead><tbody>";
            $totLoaned = 0; $totRepaid = 0;
            foreach ($loans as $l) {
                $remaining  = max(0, (float)$l['amount'] - (float)$l['repaid']);
                $totLoaned += (float)$l['amount']; $totRepaid += (float)$l['repaid'];
                $sBadge = ['active' => 'badge-warning', 'paid' => 'badge-success', 'overdue' => 'badge-danger'][$l['status']] ?? '';
                $sLabel = ['active' => 'Activo', 'paid' => 'Pago', 'overdue' => 'Em Atraso'][$l['status']] ?? $l['status'];
                echo "<tr><td class='text-right'>" . formatMoney($l['amount']) . "</td>"
                    . "<td>" . formatDate($l['disbursement_date']) . "</td>"
                    . "<td>" . formatDate($l['due_date']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($l['repaid']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($remaining) . "</td>"
                    . "<td><span class='badge {$sBadge}'>{$sLabel}</span></td></tr>";
            }
            echo "<tr class='total-row'>"
                . "<td class='text-right'>" . formatMoney($totLoaned) . "</td><td></td><td></td>"
                . "<td class='text-right'>" . formatMoney($totRepaid) . "</td>"
                . "<td class='text-right'>" . formatMoney(max(0, $totLoaned - $totRepaid)) . "</td><td></td></tr>";
            echo "</tbody></table></div>";

            // Loan Interest
            $interest = $db->fetchAll(
                "SELECT li.reference_month, li.interest_amount, li.status
                 FROM loan_interest li WHERE li.member_id = ? AND li.cycle_id = ? ORDER BY li.reference_month",
                [$memberId, $cycleId]
            );
            if ($interest) {
                echo "<div class='section'><h2>Juros de Empréstimos</h2>
                      <table><thead><tr><th>Mês Ref</th><th class='text-right'>Valor</th><th>Status</th></tr></thead><tbody>";
                $totalI = 0;
                foreach ($interest as $i) {
                    $totalI += (float)$i['interest_amount'];
                    $sBadge = $i['status'] === 'paid' ? 'badge-success' : 'badge-warning';
                    $sLabel = $i['status'] === 'paid' ? 'Pago' : 'Pendente';
                    echo "<tr><td>" . formatDate($i['reference_month']) . "</td>"
                        . "<td class='text-right'>" . formatMoney($i['interest_amount']) . "</td>"
                        . "<td><span class='badge {$sBadge}'>{$sLabel}</span></td></tr>";
                }
                echo "<tr class='total-row'><td>TOTAL</td><td class='text-right'>" . formatMoney($totalI) . "</td><td></td></tr>";
                echo "</tbody></table></div>";
            }

            // Repayments made
            $repayments = $db->fetchAll(
                "SELECT lr.amount, lr.paid_date, l.amount AS loan_amount, l.disbursement_date
                 FROM loan_repayments lr
                 JOIN loans l ON lr.loan_id = l.id
                 WHERE l.member_id = ? AND l.cycle_id = ? ORDER BY lr.paid_date",
                [$memberId, $cycleId]
            );
            if ($repayments) {
                echo "<div class='section'><h2>Reembolsos Efectuados</h2>
                      <table><thead><tr><th>Data</th><th class='text-right'>Valor Pago</th><th class='text-right'>Empréstimo Original</th></tr></thead><tbody>";
                $totalR = 0;
                foreach ($repayments as $r) {
                    $totalR += (float)$r['amount'];
                    echo "<tr><td>" . formatDate($r['paid_date']) . "</td>"
                        . "<td class='text-right'>" . formatMoney($r['amount']) . "</td>"
                        . "<td class='text-right'>" . formatMoney($r['loan_amount'])
                        . " (desp. " . formatDate($r['disbursement_date']) . ")</td></tr>";
                }
                echo "<tr class='total-row'><td>TOTAL</td><td class='text-right'>" . formatMoney($totalR) . "</td><td></td></tr>";
                echo "</tbody></table></div>";
            }

            // Joia
            $joia = $db->fetch("SELECT * FROM joias WHERE member_id = ? AND cycle_id = ? LIMIT 1", [$memberId, $cycleId]);
            if ($joia) {
                $jBadge = $joia['status'] === 'paid' ? 'badge-success' : 'badge-danger';
                $jLabel = $joia['status'] === 'paid' ? 'Paga' : 'Pendente';
                echo "<div class='section'><h2>Jóia de Adesão</h2>";
                echo "<p>Valor: <strong>" . formatMoney($joia['amount']) . "</strong>"
                    . " &nbsp;|&nbsp; Status: <span class='badge {$jBadge}'>{$jLabel}</span>";
                if ($joia['paid_date']) echo " &nbsp;|&nbsp; Paga em: " . formatDate($joia['paid_date']);
                echo "</p></div>";
            }
            break;

        // ── LOANS STATUS ─────────────────────────────────────────────────────
        case 'loans_status':
            $statusFilter = $_GET['status'] ?? '';
            $statusClause = '';
            $params = [$cycleId];
            if ($statusFilter && in_array($statusFilter, ['active', 'paid', 'overdue'], true)) {
                $statusClause = " AND l.status = ?";
                $params[] = $statusFilter;
            }
            $loans = $db->fetchAll(
                "SELECT l.*, m.full_name,
                        COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id), 0) AS repaid
                 FROM loans l JOIN members m ON l.member_id = m.id
                 WHERE l.cycle_id = ?{$statusClause} ORDER BY l.status, m.full_name",
                $params
            );
            $filterLabels = ['active' => 'Activos', 'paid' => 'Pagos', 'overdue' => 'Em Atraso'];
            $filterLabel  = $statusFilter ? ' (' . ($filterLabels[$statusFilter] ?? $statusFilter) . ')' : '';
            echo "<div class='section'><h2>Estado de Empréstimos{$filterLabel}</h2></div>";
            echo "<table><thead><tr>
                    <th>Membro</th><th class='text-right'>Valor</th><th>Desembolso</th><th>Vencimento</th>
                    <th class='text-right'>Reembolsado</th><th class='text-right'>Saldo</th><th>Status</th>
                  </tr></thead><tbody>";
            $tLoaned = 0; $tRepaid = 0;
            foreach ($loans as $l) {
                $remaining = max(0, (float)$l['amount'] - (float)$l['repaid']);
                $tLoaned  += (float)$l['amount']; $tRepaid += (float)$l['repaid'];
                $sBadge   = ['active' => 'badge-warning', 'paid' => 'badge-success', 'overdue' => 'badge-danger'][$l['status']] ?? '';
                $sLabel   = ['active' => 'Activo', 'paid' => 'Pago', 'overdue' => 'Em Atraso'][$l['status']] ?? $l['status'];
                echo "<tr><td>{$l['full_name']}</td>"
                    . "<td class='text-right'>" . formatMoney($l['amount']) . "</td>"
                    . "<td>" . formatDate($l['disbursement_date']) . "</td>"
                    . "<td>" . formatDate($l['due_date']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($l['repaid']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($remaining) . "</td>"
                    . "<td><span class='badge {$sBadge}'>{$sLabel}</span></td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($loans) . " empréstimos)</td>"
                . "<td class='text-right'>" . formatMoney($tLoaned) . "</td><td></td><td></td>"
                . "<td class='text-right'>" . formatMoney($tRepaid) . "</td>"
                . "<td class='text-right'>" . formatMoney(max(0, $tLoaned - $tRepaid)) . "</td><td></td></tr>";
            echo "</tbody></table>";
            break;

        // ── BALANCE ──────────────────────────────────────────────────────────
        case 'balance':
            $totalContrib  = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE cycle_id = ?", [$cycleId])['t'];
            $totalLateFees = (float)$db->fetch("SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE cycle_id = ? AND is_late = 1", [$cycleId])['t'];
            $totalJoias    = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM joias WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
            $totalInterest = (float)$db->fetch("SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
            $totalLoaned   = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM loans WHERE cycle_id = ?", [$cycleId])['t'];
            $totalRepaid   = (float)$db->fetch(
                "SELECT COALESCE(SUM(lr.amount),0) as t FROM loan_repayments lr JOIN loans l ON lr.loan_id = l.id WHERE l.cycle_id = ?",
                [$cycleId]
            )['t'];
            // Active outstanding principal (not counting fully-paid or rolled loans)
            $activeDebt = (float)$db->fetch(
                "SELECT COALESCE(SUM(l.amount - COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id),0)),0) as t
                 FROM loans l WHERE l.cycle_id = ? AND l.status IN ('active','overdue')",
                [$cycleId]
            )['t'];

            $totalIn = $totalContrib + $totalLateFees + $totalJoias + $totalInterest;
            // Cash balance = inflows – active outstanding debt
            $balance = $totalIn - $activeDebt;

            echo "<div class='kpi-grid'>";
            echo "<div class='summary-box'><div class='label'>Contribuições</div><div class='value' style='color:#0d9488;'>" . formatMoney($totalContrib) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Jóias</div><div class='value' style='color:#0d9488;'>" . formatMoney($totalJoias) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Multas</div><div class='value' style='color:#f59e0b;'>" . formatMoney($totalLateFees) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Juros Recebidos</div><div class='value' style='color:#0ea5e9;'>" . formatMoney($totalInterest) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Dívida Activa</div><div class='value' style='color:#dc2626;'>" . formatMoney($activeDebt) . "</div></div>";
            echo "<div class='summary-box' style='border-color:#1a73e8;'><div class='label'>Saldo em Caixa</div>"
                . "<div class='value' style='color:" . ($balance >= 0 ? '#065f46' : '#991b1b') . ";'>" . formatMoney($balance) . "</div></div>";
            echo "</div>";

            echo "<table><thead><tr><th>Descrição</th><th class='text-right'>Valor</th></tr></thead><tbody>";
            echo "<tr><td>Total Contribuições</td><td class='text-right'>" . formatMoney($totalContrib) . "</td></tr>";
            echo "<tr><td>Total Jóias</td><td class='text-right'>" . formatMoney($totalJoias) . "</td></tr>";
            echo "<tr><td>Total Multas de Atraso</td><td class='text-right'>" . formatMoney($totalLateFees) . "</td></tr>";
            echo "<tr><td>Total Juros de Empréstimos</td><td class='text-right'>" . formatMoney($totalInterest) . "</td></tr>";
            echo "<tr class='total-row'><td>TOTAL ENTRADAS</td><td class='text-right'>" . formatMoney($totalIn) . "</td></tr>";
            echo "<tr><td>Total Emprestado (histórico do ciclo)</td><td class='text-right'>(" . formatMoney($totalLoaned) . ")</td></tr>";
            echo "<tr><td>Total Reembolsado</td><td class='text-right'>" . formatMoney($totalRepaid) . "</td></tr>";
            echo "<tr><td style='color:#dc2626;'>Dívida em Aberto (activos + vencidos)</td><td class='text-right' style='color:#dc2626;'>(" . formatMoney($activeDebt) . ")</td></tr>";
            echo "<tr class='total-row' style='font-size:13px;'><td>SALDO EM CAIXA</td>"
                . "<td class='text-right' style='color:" . ($balance >= 0 ? '#065f46' : '#991b1b') . ";'>" . formatMoney($balance) . "</td></tr>";
            echo "</tbody></table>";
            break;

        // ── MONTHLY REPORT ───────────────────────────────────────────────────
        case 'monthly_report':
            $month      = $_GET['month'] ?? date('Y-m');
            $monthStart = $month . '-01';
            $monthEnd   = date('Y-m-t', strtotime($monthStart));

            // Portuguese month name — no deprecated strftime
            $monthNum   = (int)date('n', strtotime($monthStart));
            $monthLabel = $ptMonthFull[$monthNum - 1] . ' ' . date('Y', strtotime($monthStart));
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
            echo "<div class='section'><h2>Contribuições Recebidas</h2>"
                . "<table><thead><tr><th>Membro</th><th class='text-right'>Valor</th><th>Data Pgto</th>"
                . "<th class='text-right'>Multa</th><th>Método</th><th>Status</th></tr></thead><tbody>";
            foreach ($contribs as $c) {
                $totalC += (float)$c['amount']; $totalF += (float)$c['late_fee'];
                $badge = $c['is_late'] ? "<span class='badge badge-danger'>Atraso</span>" : "<span class='badge badge-success'>OK</span>";
                echo "<tr><td>{$c['full_name']}</td><td class='text-right'>" . formatMoney($c['amount']) . "</td>"
                    . "<td>" . formatDate($c['paid_date']) . "</td><td class='text-right'>" . formatMoney($c['late_fee']) . "</td>"
                    . "<td>" . ($c['payment_method'] ?? '—') . "</td><td>$badge</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($contribs) . ")</td><td class='text-right'>" . formatMoney($totalC) . "</td>"
                . "<td></td><td class='text-right'>" . formatMoney($totalF) . "</td><td></td><td></td></tr>";
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
            echo "<div class='section'><h2>Empréstimos Desembolsados</h2>"
                . "<table><thead><tr><th>Membro</th><th class='text-right'>Valor</th><th>Data Desembolso</th><th>Vencimento</th></tr></thead><tbody>";
            foreach ($loans as $l) {
                $totalL += (float)$l['amount'];
                echo "<tr><td>{$l['full_name']}</td><td class='text-right'>" . formatMoney($l['amount']) . "</td>"
                    . "<td>" . formatDate($l['disbursement_date']) . "</td><td>" . formatDate($l['due_date']) . "</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($loans) . ")</td><td class='text-right'>" . formatMoney($totalL) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // 3. Repayments Received
            $repayments = $db->fetchAll(
                "SELECT m.full_name, lr.amount, lr.paid_date, l.amount AS loan_amount
                 FROM loan_repayments lr
                 JOIN loans l ON lr.loan_id = l.id
                 JOIN members m ON l.member_id = m.id
                 WHERE l.cycle_id = ? AND lr.paid_date BETWEEN ? AND ?
                 ORDER BY lr.paid_date",
                [$cycleId, $monthStart, $monthEnd]
            );
            $totalR = 0;
            echo "<div class='section'><h2>Reembolsos Recebidos</h2>"
                . "<table><thead><tr><th>Membro</th><th class='text-right'>Valor Pago</th><th>Data</th><th class='text-right'>Empréstimo Original</th></tr></thead><tbody>";
            foreach ($repayments as $r) {
                $totalR += (float)$r['amount'];
                echo "<tr><td>{$r['full_name']}</td><td class='text-right'>" . formatMoney($r['amount']) . "</td>"
                    . "<td>" . formatDate($r['paid_date']) . "</td><td class='text-right'>" . formatMoney($r['loan_amount']) . "</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($repayments) . ")</td><td class='text-right'>" . formatMoney($totalR) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // 4. Interest Charged
            $interest = $db->fetchAll(
                "SELECT m.full_name, li.interest_amount, li.reference_month, li.status
                 FROM loan_interest li JOIN members m ON li.member_id = m.id
                 WHERE li.cycle_id = ? AND li.reference_month = ?
                 ORDER BY m.full_name",
                [$cycleId, $monthStart]
            );
            $totalI = 0;
            echo "<div class='section'><h2>Juros Cobrados</h2>"
                . "<table><thead><tr><th>Membro</th><th class='text-right'>Valor</th><th>Mês Ref</th><th>Status</th></tr></thead><tbody>";
            foreach ($interest as $i) {
                $totalI += (float)$i['interest_amount'];
                $sBadge = $i['status'] === 'paid' ? 'badge-success' : 'badge-warning';
                $sLabel = $i['status'] === 'paid' ? 'Pago' : 'Pendente';
                echo "<tr><td>{$i['full_name']}</td><td class='text-right'>" . formatMoney($i['interest_amount']) . "</td>"
                    . "<td>" . formatDate($i['reference_month']) . "</td><td><span class='badge {$sBadge}'>{$sLabel}</span></td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL (" . count($interest) . ")</td><td class='text-right'>" . formatMoney($totalI) . "</td><td></td><td></td></tr>";
            echo "</tbody></table></div>";

            // Monthly Summary
            $netFlow = $totalC + $totalF + $totalI + $totalR - $totalL;
            echo "<div class='section'><h2>Resumo do Mês</h2><div class='kpi-grid'>";
            echo "<div class='summary-box'><div class='label'>Contribuições</div><div class='value' style='color:#0d9488;'>+" . formatMoney($totalC) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Multas</div><div class='value' style='color:#f59e0b;'>+" . formatMoney($totalF) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Juros</div><div class='value' style='color:#0ea5e9;'>+" . formatMoney($totalI) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Reembolsos</div><div class='value' style='color:#0d9488;'>+" . formatMoney($totalR) . "</div></div>";
            echo "<div class='summary-box'><div class='label'>Empréstimos</div><div class='value' style='color:#dc2626;'>-" . formatMoney($totalL) . "</div></div>";
            echo "<div class='summary-box' style='border-color:#1a73e8;'><div class='label'>Fluxo Líquido</div>"
                . "<div class='value' style='color:" . ($netFlow >= 0 ? '#0d9488' : '#dc2626') . ";'>" . formatMoney($netFlow) . "</div></div>";
            echo "</div></div>";
            break;

        // ── CONTRIBUTIONS MAP ────────────────────────────────────────────────
        case 'contributions_map':
            $members = $db->fetchAll(
                "SELECT m.id, m.full_name FROM members m
                 JOIN member_cycles mc ON m.id = mc.member_id
                 WHERE mc.cycle_id = ? AND mc.status = 'active'
                 ORDER BY m.full_name", [$cycleId]
            );
            $contribs = $db->fetchAll(
                "SELECT member_id, DATE_FORMAT(reference_month, '%Y-%m') as ref_month, SUM(amount) as total
                 FROM contributions WHERE cycle_id = ?
                 GROUP BY member_id, ref_month",
                [$cycleId]
            );
            $lookup = []; $allMonths = [];
            foreach ($contribs as $c) {
                $lookup[$c['member_id']][$c['ref_month']] = (float)$c['total'];
                $allMonths[$c['ref_month']] = true;
            }
            ksort($allMonths);
            $months = array_keys($allMonths);

            // Portuguese month abbreviations
            $monthLabels = [];
            foreach ($months as $m) {
                $dt = new DateTime($m . '-01');
                $monthLabels[$m] = $ptMonthAbbr[(int)$dt->format('n') - 1] . '/' . $dt->format('y');
            }

            echo "<div class='section'><h2>Mapa de Contribuições — " . sanitize($cycle['name']) . "</h2></div>";
            echo "<div style='overflow-x:auto;'>";
            echo "<table><thead><tr><th>Membro</th>";
            foreach ($months as $m) echo "<th class='text-right' style='min-width:70px;'>{$monthLabels[$m]}</th>";
            echo "<th class='text-right' style='min-width:85px;font-weight:700;'>TOTAL</th></tr></thead><tbody>";

            $colTotals = array_fill_keys($months, 0);
            $grandTotal = 0;
            foreach ($members as $member) {
                $rowTotal = 0;
                echo "<tr><td style='white-space:nowrap;'>{$member['full_name']}</td>";
                foreach ($months as $m) {
                    $val = $lookup[$member['id']][$m] ?? 0;
                    $rowTotal += $val; $colTotals[$m] += $val;
                    echo $val > 0
                        ? "<td class='text-right'>" . formatMoney($val) . "</td>"
                        : "<td class='text-right' style='color:#dc2626;'>—</td>";
                }
                $grandTotal += $rowTotal;
                echo "<td class='text-right' style='font-weight:600;'>" . formatMoney($rowTotal) . "</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL</td>";
            foreach ($months as $m) echo "<td class='text-right'>" . formatMoney($colTotals[$m]) . "</td>";
            echo "<td class='text-right'>" . formatMoney($grandTotal) . "</td></tr>";
            echo "</tbody></table></div>";
            break;

        // ── JOIA STATUS ──────────────────────────────────────────────────────
        case 'joia_status':
            $members = $db->fetchAll(
                "SELECT m.id, m.full_name,
                        j.amount, j.status, j.paid_date, j.notes
                 FROM members m
                 JOIN member_cycles mc ON m.id = mc.member_id
                 LEFT JOIN joias j ON j.member_id = m.id AND j.cycle_id = ?
                 WHERE mc.cycle_id = ? AND mc.status = 'active'
                 ORDER BY m.full_name",
                [$cycleId, $cycleId]
            );
            $paidCount = 0; $pendingCount = 0; $totalAmt = 0;
            foreach ($members as $m) {
                if (($m['status'] ?? '') === 'paid') { $paidCount++; $totalAmt += (float)($m['amount'] ?? 0); }
                else $pendingCount++;
            }
            echo "<div class='kpi-grid'>";
            echo "<div class='summary-box'><div class='label'>Pagas</div><div class='value' style='color:#065f46;'>{$paidCount}</div></div>";
            echo "<div class='summary-box'><div class='label'>Pendentes</div><div class='value' style='color:#dc2626;'>{$pendingCount}</div></div>";
            echo "<div class='summary-box'><div class='label'>Total Recebido</div><div class='value'>" . formatMoney($totalAmt) . "</div></div>";
            echo "</div>";
            echo "<table><thead><tr><th>Membro</th><th class='text-right'>Valor</th><th>Data Pgto</th><th>Status</th><th>Notas</th></tr></thead><tbody>";
            foreach ($members as $m) {
                $isPaid   = ($m['status'] ?? '') === 'paid';
                $badge    = $isPaid ? "<span class='badge badge-success'>Paga</span>" : "<span class='badge badge-danger'>Pendente</span>";
                $rowStyle = $isPaid ? '' : " style='background:#fff5f5;'";
                echo "<tr{$rowStyle}><td>{$m['full_name']}</td>"
                    . "<td class='text-right'>" . formatMoney($m['amount'] ?? 0) . "</td>"
                    . "<td>" . ($m['paid_date'] ? formatDate($m['paid_date']) : '—') . "</td>"
                    . "<td>{$badge}</td>"
                    . "<td style='color:#64748b;'>" . sanitize($m['notes'] ?? '') . "</td></tr>";
            }
            echo "</tbody></table>";
            break;

        // ── DISTRIBUTION REPORT ──────────────────────────────────────────────
        case 'distribution_report':
            $distributions = $db->fetchAll(
                "SELECT d.type, d.amount, d.description, d.distributed_at, m.full_name
                 FROM distributions d JOIN members m ON d.member_id = m.id
                 WHERE d.cycle_id = ? ORDER BY m.full_name, d.type",
                [$cycleId]
            );
            $typeLabels = ['interest' => 'Juros Fixos', 'surplus' => 'Excedente', 'late_fee' => 'Multas'];

            if (empty($distributions)) {
                echo "<div class='section'><p style='color:#64748b;padding:1rem 0;'>Nenhuma distribuição registada para este ciclo.</p></div>";
                break;
            }

            // Totals by type
            $byType = [];
            foreach ($distributions as $d) {
                $byType[$d['type']] = ($byType[$d['type']] ?? 0) + (float)$d['amount'];
            }
            echo "<div class='kpi-grid'>";
            foreach ($byType as $t => $amt) {
                echo "<div class='summary-box'><div class='label'>" . ($typeLabels[$t] ?? $t) . "</div><div class='value'>" . formatMoney($amt) . "</div></div>";
            }
            $grandTotal = array_sum($byType);
            echo "<div class='summary-box' style='border-color:#1a73e8;'><div class='label'>TOTAL DISTRIBUÍDO</div><div class='value' style='color:#1a73e8;'>" . formatMoney($grandTotal) . "</div></div>";
            echo "</div>";

            // Per-member rollup
            $memberTotals = [];
            foreach ($distributions as $d) {
                $n = $d['full_name'];
                if (!isset($memberTotals[$n])) {
                    $memberTotals[$n] = ['interest' => 0, 'surplus' => 0, 'late_fee' => 0, 'total' => 0, 'date' => $d['distributed_at']];
                }
                $memberTotals[$n][$d['type']] = (float)$d['amount'];
                $memberTotals[$n]['total']    += (float)$d['amount'];
            }
            echo "<table><thead><tr>"
                . "<th>Membro</th><th class='text-right'>Juros Fixos</th><th class='text-right'>Excedente</th>"
                . "<th class='text-right'>Multas</th><th class='text-right' style='font-weight:700;'>TOTAL</th><th>Data</th>"
                . "</tr></thead><tbody>";
            $totI = 0; $totS = 0; $totLF = 0; $totT = 0;
            foreach ($memberTotals as $name => $mt) {
                $totI += $mt['interest']; $totS += $mt['surplus']; $totLF += $mt['late_fee']; $totT += $mt['total'];
                echo "<tr><td>{$name}</td>"
                    . "<td class='text-right'>" . formatMoney($mt['interest']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($mt['surplus']) . "</td>"
                    . "<td class='text-right'>" . formatMoney($mt['late_fee']) . "</td>"
                    . "<td class='text-right' style='font-weight:600;'>" . formatMoney($mt['total']) . "</td>"
                    . "<td>" . (isset($mt['date']) ? date('d/m/Y', strtotime($mt['date'])) : '—') . "</td></tr>";
            }
            echo "<tr class='total-row'><td>TOTAL</td>"
                . "<td class='text-right'>" . formatMoney($totI) . "</td>"
                . "<td class='text-right'>" . formatMoney($totS) . "</td>"
                . "<td class='text-right'>" . formatMoney($totLF) . "</td>"
                . "<td class='text-right'>" . formatMoney($totT) . "</td><td></td></tr>";
            echo "</tbody></table>";
            break;

        // ── ELIGIBILITY REPORT ───────────────────────────────────────────────
        case 'eligibility_report':
            $minMovement = (float)($cycle['min_loan_movement'] ?? 50000);
            $members = $db->fetchAll(
                "SELECT m.id, m.full_name FROM members m
                 JOIN member_cycles mc ON m.id = mc.member_id
                 WHERE mc.cycle_id = ? AND mc.status = 'active'
                 ORDER BY m.full_name",
                [$cycleId]
            );
            echo "<div class='section'><h2>Elegibilidade para Distribuição de Juros</h2>"
                . "<p style='color:#64748b;margin:.4rem 0;'>Limiar de movimentação em empréstimos: <strong>" . formatMoney($minMovement) . "</strong></p></div>";
            echo "<table><thead><tr>"
                . "<th>Membro</th><th class='text-right'>Contribuições</th>"
                . "<th class='text-right'>Movimentação (Empréstimos)</th><th class='text-center'>Elegível</th>"
                . "</tr></thead><tbody>";
            $eligibleCount = 0;
            foreach ($members as $member) {
                $savings  = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE member_id=? AND cycle_id=?", [$member['id'], $cycleId])['t'];
                $movement = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM loans WHERE member_id=? AND cycle_id=?", [$member['id'], $cycleId])['t'];
                $eligible = $movement >= $minMovement;
                if ($eligible) $eligibleCount++;
                $badge    = $eligible ? "<span class='badge badge-success'>Sim</span>" : "<span class='badge badge-danger'>Não</span>";
                $rowStyle = $eligible ? '' : " style='opacity:.8;'";
                echo "<tr{$rowStyle}><td>{$member['full_name']}</td>"
                    . "<td class='text-right'>" . formatMoney($savings) . "</td>"
                    . "<td class='text-right'>" . formatMoney($movement) . "</td>"
                    . "<td class='text-center'>{$badge}</td></tr>";
            }
            $total = count($members);
            echo "<tr class='total-row'><td>TOTAL: {$total} membros</td><td></td><td></td><td class='text-center'>{$eligibleCount} elegíveis</td></tr>";
            echo "</tbody></table>";
            break;
    }

    echo '</body></html>';
    exit;
}
