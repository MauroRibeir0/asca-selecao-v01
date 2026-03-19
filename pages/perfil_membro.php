<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Perfil do Membro';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = Database::getInstance();
$cycle = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;

$memberId = (int)($_GET['id'] ?? 0);
if (!$memberId) { redirect(BASE_URL . '/pages/membros.php'); }

$member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
if (!$member) {
    setFlashMessage('danger', 'Membro não encontrado.');
    redirect(BASE_URL . '/pages/membros.php');
}

// ════════════════════════════════════════
// DATA — same queries used in member portal
// ════════════════════════════════════════
$totalContrib = $db->fetch(
    "SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ?",
    [$memberId, $cycleId])['t'];

$totalLateFees = $db->fetch(
    "SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE member_id = ? AND cycle_id = ? AND is_late = 1",
    [$memberId, $cycleId])['t'];

$joia = $db->fetch("SELECT * FROM joias WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId]);
$totalPoupado = $totalContrib + ($joia && $joia['status'] === 'paid' ? (float)$joia['amount'] : 0);

$activeLoan = $db->fetch(
    "SELECT * FROM loans WHERE member_id = ? AND cycle_id = ? AND status IN ('active','overdue') LIMIT 1",
    [$memberId, $cycleId]);

$totalRepaid = 0;
if ($activeLoan) {
    $totalRepaid = $db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM loan_repayments WHERE loan_id = ?", [$activeLoan['id']])['t'];
}

// Contributions list
$contributions = $db->fetchAll(
    "SELECT * FROM contributions WHERE member_id = ? AND cycle_id = ? ORDER BY reference_month DESC",
    [$memberId, $cycleId]);

$contribTotal = array_sum(array_column($contributions, 'amount'));
$contribFees  = array_sum(array_map(fn($c) => (float)$c['late_fee'], $contributions));

// Loans list
$loans = $db->fetchAll(
    "SELECT l.*, 
            (SELECT COALESCE(SUM(lr.amount),0) FROM loan_repayments lr WHERE lr.loan_id = l.id) as total_repaid,
            (SELECT COALESCE(SUM(li.interest_amount),0) FROM loan_interest li WHERE li.loan_id = l.id AND li.status = 'paid') as total_interest
     FROM loans l WHERE l.member_id = ? AND l.cycle_id = ? ORDER BY l.disbursement_date DESC",
    [$memberId, $cycleId]);

// Extracto timeline
$transactions = [];
$contribs = $db->fetchAll("SELECT 'contribution' as type, paid_date as date, amount, reference_month, is_late, late_fee FROM contributions WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId]);
foreach ($contribs as $c) {
    $label = 'Contribuição (' . formatDate($c['reference_month']) . ')';
    if ($c['is_late']) $label .= ' + Multa: ' . formatMoney($c['late_fee']);
    $transactions[] = ['date' => $c['date'], 'type' => 'credit', 'label' => $label, 'amount' => $c['amount'], 'icon' => 'bi-cash-coin text-success'];
}
$joias = $db->fetchAll("SELECT paid_date as date, amount FROM joias WHERE member_id = ? AND cycle_id = ? AND status = 'paid'", [$memberId, $cycleId]);
foreach ($joias as $j) {
    $transactions[] = ['date' => $j['date'], 'type' => 'credit', 'label' => 'Jóia', 'amount' => $j['amount'], 'icon' => 'bi-gem text-primary'];
}
$loanRows = $db->fetchAll("SELECT id, disbursement_date as date, amount FROM loans WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId]);
foreach ($loanRows as $l) {
    $transactions[] = ['date' => $l['date'], 'type' => 'debit', 'label' => 'Empréstimo Recebido', 'amount' => $l['amount'], 'icon' => 'bi-arrow-down-circle text-info'];
    $reps = $db->fetchAll("SELECT paid_date as date, amount FROM loan_repayments WHERE loan_id = ?", [$l['id']]);
    foreach ($reps as $r) {
        $transactions[] = ['date' => $r['date'], 'type' => 'credit', 'label' => 'Reembolso Empréstimo', 'amount' => $r['amount'], 'icon' => 'bi-arrow-up-circle text-success'];
    }
}
usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']));

// Movement total (for 50k rule)
$totalLoanedMember = (float)$db->fetch("SELECT COALESCE(SUM(amount),0) as t FROM loans WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId])['t'];
$minMovement = $cycle ? (float)$cycle['min_loan_movement'] : 50000;
$movementPct = $minMovement > 0 ? min(100, round(($totalLoanedMember / $minMovement) * 100)) : 0;

// ── DATA FOR GROUP KPI (Global Situation) ──────────
$groupContribs = $db->fetch("SELECT COALESCE(SUM(amount), 0) as t FROM contributions WHERE cycle_id = ?", [$cycleId])['t'];
$groupLateFees = $db->fetch("SELECT COALESCE(SUM(late_fee), 0) as t FROM contributions WHERE cycle_id = ? AND is_late = 1", [$cycleId])['t'];
$groupJoias    = $db->fetch("SELECT COALESCE(SUM(amount), 0) as t FROM joias WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];
$groupInterest = $db->fetch("SELECT COALESCE(SUM(interest_amount), 0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'paid'", [$cycleId])['t'];

// Get current active debt (sum of active/overdue loans only) to avoid rollover duplication
$groupActiveDebt = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as t FROM loans WHERE cycle_id = ? AND status IN ('active', 'overdue')",
    [$cycleId]
)['t'];

$activeDebt = (float)$groupActiveDebt;
$fundoTotalGrupo = (float)$groupContribs + (float)$groupLateFees + (float)$groupJoias + (float)$groupInterest;
$capitalDisponivel = $fundoTotalGrupo - $activeDebt;

// Member Loan Capacity (Earnings share)
$groupSavingsTotal = (float)$groupContribs + (float)$groupJoias;
$myInterestShare = $groupSavingsTotal > 0 ? ((float)$totalPoupado / $groupSavingsTotal) * (float)$groupInterest : 0;
$tolerance = (float)($cycle['loan_tolerance_margin'] ?? 0);
$capacidadeIndividual = (float)$totalPoupado + $myInterestShare + $tolerance;

$activeTab = $_GET['tab'] ?? 'resumo';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-person-badge me-2"></i><?= sanitize($member['full_name']) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="membros.php">Membros</a></li>
                <li class="breadcrumb-item active"><?= sanitize($member['full_name']) ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <?php if ($joia && $joia['status'] !== 'paid'): ?>
        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalJoia">
            <i class="bi bi-gem me-1"></i>Registar Jóia
        </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/api/reports_api.php?report=member_extract&member_id=<?= $memberId ?>&cycle_id=<?= $cycleId ?>" 
           target="_blank" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimir Extracto
        </a>
        <a href="membros.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<!-- Member Info Card (same as meu_extracto.php) -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-lg-3">
                <div class="text-center">
                    <div style="width:80px;height:80px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;font-weight:700;margin:0 auto .75rem;">
                        <?= strtoupper(substr($member['full_name'], 0, 2)) ?>
                    </div>
                    <h5 class="mb-1"><?= sanitize($member['full_name']) ?></h5>
                    <?php
                    $sB = ['active'=>'<span class="badge bg-success">Activo</span>','inactive'=>'<span class="badge bg-secondary">Inactivo</span>','suspended'=>'<span class="badge bg-warning text-dark">Suspenso</span>'];
                    echo $sB[$member['status']] ?? '';
                    ?>
                </div>
            </div>
            <div class="col-md-8 col-lg-9">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-4">
                        <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i>Telefone</small>
                        <strong><?= sanitize($member['phone'] ?: '—') ?></strong>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <small class="text-muted d-block"><i class="bi bi-envelope me-1"></i>Email</small>
                        <strong><?= sanitize($member['email'] ?: '—') ?></strong>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <small class="text-muted d-block"><i class="bi bi-card-text me-1"></i>BI / NUIT</small>
                        <strong><?= sanitize($member['id_number'] ?: '—') ?></strong>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i>Endereço</small>
                        <strong><?= sanitize($member['address'] ?: '—') ?></strong>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <small class="text-muted d-block"><i class="bi bi-calendar-event me-1"></i>Data de Adesão</small>
                        <strong><?= formatDate($member['join_date']) ?></strong>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <small class="text-muted d-block"><i class="bi bi-gem me-1"></i>Jóia</small>
                        <strong>
                            <?php if ($joia): ?>
                                <?= $joia['status'] === 'paid' 
                                    ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Paga (' . formatDate($joia['paid_date']) . ')</span>' 
                                    : '<span class="text-warning"><i class="bi bi-clock me-1"></i>Pendente</span>' ?>
                            <?php else: ?>—<?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs (mirrors sidebar member menu) -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'resumo' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabResumo">
            <i class="bi bi-house-door me-1"></i>Resumo
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'contribuicoes' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabContrib">
            <i class="bi bi-cash-coin me-1"></i>Contribuições <span class="badge bg-primary ms-1"><?= count($contributions) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'emprestimos' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabLoans">
            <i class="bi bi-bank me-1"></i>Empréstimos <span class="badge bg-primary ms-1"><?= count($loans) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'extracto' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabExtracto">
            <i class="bi bi-file-earmark-text me-1"></i>Extracto
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ════════════════════════════════════════
     TAB: RESUMO (mirrors meu_resumo.php)
     ════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab === 'resumo' ? 'show active' : '' ?>" id="tabResumo">

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl">
            <div class="kpi-card kpi-success bg-white">
                <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
                <div class="kpi-value"><?= formatMoney($totalPoupado) ?></div>
                <div class="kpi-label">Total Poupado</div>
            </div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="kpi-card kpi-primary bg-white">
                <div class="kpi-icon"><i class="bi bi-gem"></i></div>
                <div class="kpi-value"><?= $joia ? ($joia['status'] === 'paid' ? '<span class="text-success">Paga</span>' : '<span class="text-warning">Pendente</span>') : '—' ?></div>
                <div class="kpi-label">Jóia (<?= formatMoney($joia['amount'] ?? 0) ?>)</div>
            </div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="kpi-card <?= $activeLoan ? ($activeLoan['status'] === 'overdue' ? 'kpi-danger' : 'kpi-warning') : 'kpi-info' ?> bg-white">
                <div class="kpi-icon"><i class="bi bi-bank"></i></div>
                <div class="kpi-value"><?= $activeLoan ? formatMoney($activeLoan['amount']) : 'Nenhum' ?></div>
                <div class="kpi-label">Empréstimo Activo</div>
            </div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="kpi-card kpi-warning bg-white">
                <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="kpi-value text-warning"><?= formatMoney($totalLateFees) ?></div>
                <div class="kpi-label">Total de Multas</div>
            </div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="kpi-card kpi-info bg-white">
                <div class="kpi-icon"><i class="bi bi-person-check"></i></div>
                <div class="kpi-value text-primary"><?= formatMoney($capacidadeIndividual) ?></div>
                <div class="kpi-label">Capacidade de Empréstimo</div>
            </div>
        </div>
    </div>

    <!-- Situação do Grupo (added for consistency) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6 border-end-md">
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle me-3">
                                    <i class="bi bi-safe fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Fundo Total Acumulado (Grupo)</small>
                                    <span class="fw-bold fs-5"><?= formatMoney($fundoTotalGrupo) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle me-3">
                                    <i class="bi bi-bank fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Capital Disponível (Grupo)</small>
                                    <span class="fw-bold fs-5 text-primary"><?= formatMoney($capitalDisponivel) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($activeLoan): ?>
    <!-- Active Loan Detail (same layout as meu_resumo.php) -->
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

    <!-- Movement Progress (50k rule) -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Movimentação Mínima Obrigatória</h6>
                <span class="badge <?= $movementPct >= 100 ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= formatMoney($totalLoanedMember) ?> / <?= formatMoney($minMovement) ?>
                </span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar <?= $movementPct >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $movementPct ?>%"></div>
            </div>
            <small class="text-muted">
                <?= $movementPct ?>% alcançado
                <?= $movementPct >= 100 ? ' — <span class="text-success">Elegível para juros fixos de ' . formatMoney($cycle['fixed_interest_entitlement'] ?? 7500) . '</span>' : '' ?>
            </small>
        </div>
    </div>

    <!-- Recent Contributions (same as meu_resumo.php) -->
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
                    $recentContribs = array_slice($contributions, 0, 6);
                    if (empty($recentContribs)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sem contribuições registadas</td></tr>
                    <?php else:
                        foreach ($recentContribs as $c):
                            $badge = $c['is_late'] ? '<span class="badge bg-danger">Atraso</span>' : '<span class="badge bg-success">OK</span>';
                    ?>
                        <tr>
                            <td><?= formatDate($c['reference_month']) ?></td>
                            <td><?= formatMoney($c['amount']) ?></td>
                            <td><?= formatDate($c['paid_date']) ?></td>
                            <td><?= $badge ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════
     TAB: CONTRIBUIÃ‡Ã•ES (mirrors minhas_contribuicoes.php)
     ════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab === 'contribuicoes' ? 'show active' : '' ?>" id="tabContrib">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="kpi-card kpi-success bg-white">
                <div class="kpi-value"><?= formatMoney($contribTotal) ?></div>
                <div class="kpi-label">Total Contribuído</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card kpi-primary bg-white">
                <div class="kpi-value"><?= count($contributions) ?></div>
                <div class="kpi-label">Pagamentos Feitos</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card kpi-warning bg-white">
                <div class="kpi-value"><?= formatMoney($contribFees) ?></div>
                <div class="kpi-label">Total de Multas</div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Mês Referência</th><th>Valor</th><th>Data Pagamento</th><th>Data Limite</th><th>Multa</th><th>Método</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($contributions)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sem contribuições</td></tr>
                    <?php else: foreach ($contributions as $c):
                        $methods = ['cash' => 'Dinheiro', 'mpesa' => 'M-Pesa', 'bank_transfer' => 'Transf.'];
                        $badge = $c['is_late'] ? '<span class="badge bg-danger">Atraso</span>' : '<span class="badge bg-success">No prazo</span>';
                    ?>
                    <tr>
                        <td><?= formatDate($c['reference_month']) ?></td>
                        <td><?= formatMoney($c['amount']) ?></td>
                        <td><?= formatDate($c['paid_date']) ?></td>
                        <td><?= formatDate($c['due_date']) ?></td>
                        <td><?= $c['is_late'] ? formatMoney($c['late_fee']) : '—' ?></td>
                        <td><span class="badge bg-secondary"><?= $methods[$c['payment_method']] ?? $c['payment_method'] ?></span></td>
                        <td><?= $badge ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════
     TAB: EMPRÉSTIMOS (mirrors meus_emprestimos.php)
     ════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab === 'emprestimos' ? 'show active' : '' ?>" id="tabLoans">
    <?php if (empty($loans)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-2">Não tem Empréstimos registados neste ciclo.</p>
        </div>
    </div>
    <?php else: foreach ($loans as $loan):
        $remaining = (float)$loan['amount'] - (float)$loan['total_repaid'];
        $pct = (float)$loan['amount'] > 0 ? round(((float)$loan['total_repaid'] / (float)$loan['amount']) * 100) : 0;
        $statusMap = ['active' => ['bg-primary', 'Activo'], 'overdue' => ['bg-danger', 'Em Atraso'], 'paid' => ['bg-success', 'Pago']];
        [$badgeClass, $badgeText] = $statusMap[$loan['status']] ?? ['bg-secondary', $loan['status']];
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1">Empréstimo de <?= formatMoney($loan['amount']) ?></h6>
                    <small class="text-muted">Desembolsado em <?= formatDate($loan['disbursement_date']) ?></small>
                </div>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <small class="text-muted">Valor Total</small>
                    <div class="fw-600"><?= formatMoney($loan['amount']) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Já Pago</small>
                    <div class="fw-600 text-success"><?= formatMoney($loan['total_repaid']) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Em Falta</small>
                    <div class="fw-600 text-danger"><?= formatMoney($remaining) ?></div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Juros Pagos</small>
                    <div class="fw-600 text-warning"><?= formatMoney($loan['total_interest']) ?></div>
                </div>
            </div>

            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
            </div>
            <small class="text-muted"><?= $pct ?>% concluído — Vencimento: <?= formatDate($loan['due_date']) ?></small>

            <?php
            $repayments = $db->fetchAll("SELECT * FROM loan_repayments WHERE loan_id = ? ORDER BY paid_date DESC", [$loan['id']]);
            if (!empty($repayments)):
            ?>
            <div class="mt-3">
                <h6 class="small fw-600">Histórico de Reembolsos</h6>
                <table class="table table-sm">
                    <thead><tr><th>Data</th><th>Valor</th><th>Método</th></tr></thead>
                    <tbody>
                        <?php foreach ($repayments as $r):
                            $methods = ['cash' => 'Dinheiro', 'mpesa' => 'M-Pesa', 'bank_transfer' => 'Transf.'];
                        ?>
                        <tr>
                            <td><?= formatDate($r['paid_date']) ?></td>
                            <td><?= formatMoney($r['amount']) ?></td>
                            <td><?= $methods[$r['payment_method']] ?? $r['payment_method'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- ════════════════════════════════════════
     TAB: EXTRACTO (mirrors meu_extracto.php)
     ════════════════════════════════════════ -->
<div class="tab-pane fade <?= $activeTab === 'extracto' ? 'show active' : '' ?>" id="tabExtracto">
    <div class="table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Extracto de Movimentos</h6>
            <a href="<?= BASE_URL ?>/api/reports_api.php?report=member_extract&member_id=<?= $memberId ?>&cycle_id=<?= $cycleId ?>" 
               target="_blank" class="btn btn-sm btn-primary">
                <i class="bi bi-printer me-1"></i>PDF
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Data</th><th>Descrição</th><th class="text-end">Valor</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Sem movimentos registados</td></tr>
                    <?php else: foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= formatDate($t['date']) ?></td>
                        <td><i class="bi <?= $t['icon'] ?> me-2"></i><?= $t['label'] ?></td>
                        <td class="text-end fw-600 <?= $t['type'] === 'credit' ? 'text-success' : 'text-info' ?>">
                            <?= $t['type'] === 'credit' ? '+' : '' ?><?= formatMoney($t['amount']) ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /.tab-content -->

<!-- Modal: Pay Jóia -->
<?php if ($joia && $joia['status'] !== 'paid'): ?>
<div class="modal fade" id="modalJoia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registar Pagamento de Jóia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formJoia">
                <div class="modal-body">
                    <p>Membro: <strong><?= sanitize($member['full_name']) ?></strong></p>
                    <p>Valor da jóia: <strong><?= formatMoney($joia['amount']) ?></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Data de Pagamento</label>
                        <input type="date" class="form-control" name="paid_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referência / Comprovativo</label>
                        <input type="text" class="form-control" name="receipt_ref">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check me-1"></i>Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('formJoia').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'pay_joia');
    fd.append('member_id', '<?= $memberId ?>');
    fetch('../api/membros_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { APP.showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
            else { APP.showToast(data.message, 'danger'); }
        });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


