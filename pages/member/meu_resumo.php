<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
requireRole(ROLE_MEMBER);

$pageTitle = 'Meu Resumo';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$db = Database::getInstance();
$memberId = $_SESSION['member_id'];
$cycle = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;

$member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);

// Member Financials
$totalContrib = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId])['t'];
$totalLateF = $db->fetch("SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ? AND is_late = 1", [$memberId, $cycleId])['t'];
$joia = $db->fetch("SELECT * FROM joias WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId]);
$totalPoupado = $totalContrib + ($joia && $joia['status'] === 'paid' ? (float)$joia['amount'] : 0);

// Group Financials (Global)
$groupContribs = $db->fetch("SELECT COALESCE(SUM(amount), 0) as t FROM contributions WHERE cycle_id = ?", [$cycleId])['t'];
$groupLateFees = $db->fetch("SELECT COALESCE(SUM(late_fee), 0) as t FROM contributions WHERE cycle_id = ?", [$cycleId])['t'];
$groupJoias = $db->fetch("SELECT COALESCE(SUM(amount), 0) as t FROM joias WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
$groupInterest = $db->fetch("SELECT COALESCE(SUM(interest_amount), 0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
$fundoTotalGrupo = (float)$groupContribs + (float)$groupLateFees + (float)$groupJoias + (float)$groupInterest;

// Current Active Debt (active/overdue only)
$groupActiveDebt = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as t FROM loans WHERE cycle_id = ? AND status IN ('active', 'overdue')",
    [$cycleId]
)['t'];

$activeDebt = (float)$groupActiveDebt;
$capitalDisponivel = $fundoTotalGrupo - $activeDebt;

// Member Loan Capacity
// Proportional share of group interest: (Member Savings / Group Savings) * Group Interest
$groupSavingsTotal = (float)$groupContribs + (float)$groupJoias; // Base for distribution usually excludes late fees
if ($groupSavingsTotal > 0) {
    $myInterestShare = ((float)$totalPoupado / $groupSavingsTotal) * (float)$groupInterest;
} else {
    $myInterestShare = 0;
}

$tolerance = (float)($cycle['loan_tolerance_margin'] ?? 0);
$minMovementGoal = (float)($cycle['min_loan_movement'] ?? 50000);

$capacidadeIndividual = (float)$totalPoupado + $myInterestShare + $tolerance;
$meuMovimentoTotal = $db->fetch("SELECT COALESCE(SUM(amount), 0) as t FROM loans WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId])['t'];

$activeLoan = $db->fetch("SELECT * FROM loans WHERE member_id = ? AND cycle_id = ? AND status IN ('active','overdue') LIMIT 1", [$memberId, $cycleId]);
$totalRepaid = 0;
if ($activeLoan) {
    $totalRepaid = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM loan_repayments WHERE loan_id = ?", [$activeLoan['id']])['t'];
}
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-house-door me-2"></i>Meu Resumo</h1>
        <p class="text-muted mb-0">Bem-vindo(a), <strong><?= sanitize($member['full_name']) ?></strong></p>
    </div>
</div>

<!-- Summary Cards (My Info) -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-success bg-white">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-value text-success"><?= formatMoney($totalPoupado) ?></div>
            <div class="kpi-label">Minha Poupança Total</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-info bg-white">
            <div class="kpi-icon"><i class="bi bi-person-check"></i></div>
            <div class="kpi-value text-primary"><?= formatMoney($capacidadeIndividual) ?></div>
            <div class="kpi-label">Minha Capacidade de Empréstimo</div>
            <small class="text-muted" style="font-size: 0.65rem;">(Poupança + Juros + Margem de Tolerância)</small>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card <?= $activeLoan ? ($activeLoan['status'] === 'overdue' ? 'kpi-danger' : 'kpi-warning') : 'kpi-info' ?> bg-white">
            <div class="kpi-icon"><i class="bi bi-bank"></i></div>
            <div class="kpi-value"><?= $activeLoan ? formatMoney($activeLoan['amount']) : 'Nenhum' ?></div>
            <div class="kpi-label">Empréstimo Actual</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-warning bg-white">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-value"><?= formatMoney($totalLateF) ?></div>
            <div class="kpi-label">Total de Multas</div>
        </div>
    </div>
</div>

<!-- Summary Cards (Group Status) -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-6">
        <div class="card border-0 shadow-sm h-100 bg-light">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle me-3">
                        <i class="bi bi-safe fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Situação do Grupo</h6>
                        <small class="text-muted">Acompanhe o desempenho colectivo</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Fundo Total Acumulado</small>
                        <span class="fw-bold fs-5"><?= formatMoney($fundoTotalGrupo) ?></span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Capital Disponível</small>
                        <span class="fw-bold fs-5 text-success"><?= formatMoney($capitalDisponivel) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><i class="bi bi-graph-up-arrow me-2 text-warning"></i>Meta de Movimentação Anual</h6>
                    <span class="badge bg-warning bg-opacity-10 text-dark"><?= formatMoney($meuMovimentoTotal) ?> / <?= formatMoney($minMovementGoal) ?></span>
                </div>
                <?php
                $pctGoal = $minMovementGoal > 0 ? min(100, round(((float)$meuMovimentoTotal / $minMovementGoal) * 100)) : 100;
                $progressClass = $pctGoal >= 100 ? 'bg-success' : 'bg-warning';
                ?>
                <div class="progress mb-2" style="height: 12px; border-radius: 6px;">
                    <div class="progress-bar <?= $progressClass ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $pctGoal ?>%"></div>
                </div>
                <small class="text-muted">
                    <?php if ($pctGoal >= 100): ?>
                        <i class="bi bi-patch-check-fill text-success me-1"></i>Parabéns! Já atingiu o nível mínimo de movimentação para juros fixos.
                    <?php else: ?>
                        Falta <strong><?= formatMoney(max(0, $minMovementGoal - (float)$meuMovimentoTotal)) ?></strong> para garantir o direito aos juros fixos de final de ciclo.
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php if ($activeLoan): ?>
<div class="card mb-4">
    <div class="card-body">
        <h6><i class="bi bi-bank me-2"></i>Detalhe do Empréstimo Activo</h6>
        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <small class="text-muted">Valor</small>
                <div class="fw-600"><?= formatMoney($activeLoan['amount']) ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Data Desembolso</small>
                <div><?= formatDate($activeLoan['disbursement_date']) ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Vencimento</small>
                <div class="<?= $activeLoan['status'] === 'overdue' ? 'text-danger fw-600' : '' ?>"><?= formatDate($activeLoan['due_date']) ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Já Reembolsado</small>
                <div class="fw-600"><?= formatMoney($totalRepaid) ?> / <?= formatMoney($activeLoan['amount']) ?></div>
            </div>
        </div>
        <?php
        $remaining = (float)$activeLoan['amount'] - (float)$totalRepaid;
        $pct = (float)$activeLoan['amount'] > 0 ? round(((float)$totalRepaid / (float)$activeLoan['amount']) * 100) : 0;
        ?>
        <div class="progress mt-3" style="height: 8px;">
            <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
        </div>
        <small class="text-muted"><?= $pct ?>% pago — Falta: <?= formatMoney($remaining) ?></small>
    </div>
</div>
<?php endif; ?>

<!-- Recent Contributions -->
<div class="table-card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimas Contribuições</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Mês</th><th>Valor</th><th>Data Pgto</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php
                $recentContribs = $db->fetchAll(
                    "SELECT * FROM contributions WHERE member_id = ? AND cycle_id = ? ORDER BY reference_month DESC LIMIT 6",
                    [$memberId, $cycleId]
                );
                if (empty($recentContribs)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Sem contribuições registadas</td></tr>
                <?php else:
                    foreach ($recentContribs as $c):
                        $badge = $c['is_late'] ? '<span class="badge bg-danger">Atraso</span>' : '<span class="badge bg-success">OK</span>';
                ?>
                    <tr>
                        <td><?= formatMonthName($c['reference_month']) ?></td>
                        <td><?= formatMoney($c['amount']) ?></td>
                        <td><?= formatDate($c['paid_date']) ?></td>
                        <td><?= $badge ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


