<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
requireRole(ROLE_MEMBER);

$pageTitle = 'Meu Extracto';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$db = Database::getInstance();
$memberId = $_SESSION['member_id'];
$cycle = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;
$member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-file-earmark-text me-2"></i>Meu Extracto</h1>
    </div>
    <a href="<?= BASE_URL ?>/api/reports_api.php?report=member_extract&member_id=<?= $memberId ?>&cycle_id=<?= $cycleId ?>" 
       target="_blank" class="btn btn-primary">
        <i class="bi bi-printer me-1"></i>Imprimir Extracto
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6>Dados Pessoais</h6>
        <div class="row g-3 mt-1">
            <div class="col-md-4"><small class="text-muted">Nome</small><div class="fw-600"><?= sanitize($member['full_name']) ?></div></div>
            <div class="col-md-4"><small class="text-muted">Telefone</small><div><?= sanitize($member['phone'] ?: '—') ?></div></div>
            <div class="col-md-4"><small class="text-muted">Data de Adesão</small><div><?= formatDate($member['join_date']) ?></div></div>
        </div>
    </div>
</div>

<?php
// Build timeline of all transactions
$transactions = [];

$contribs = $db->fetchAll("SELECT 'contribution' as type, paid_date as date, amount, reference_month, is_late, late_fee FROM contributions WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId]);
foreach ($contribs as $c) {
    $label = 'Contribuição (' . formatDate($c['reference_month']) . ')';
    if ($c['is_late']) $label .= ' + Multa: ' . formatMoney($c['late_fee']);
    $transactions[] = ['date' => $c['date'], 'type' => 'credit', 'label' => $label, 'amount' => $c['amount'], 'icon' => 'bi-cash-coin text-success'];
}

$joias = $db->fetchAll("SELECT 'joia' as type, paid_date as date, amount FROM joias WHERE member_id = ? AND cycle_id = ? AND status = 'paid'", [$memberId, $cycleId]);
foreach ($joias as $j) {
    $transactions[] = ['date' => $j['date'], 'type' => 'credit', 'label' => 'Jóia', 'amount' => $j['amount'], 'icon' => 'bi-gem text-primary'];
}

$loans = $db->fetchAll("SELECT id, disbursement_date as date, amount FROM loans WHERE member_id = ? AND cycle_id = ?", [$memberId, $cycleId]);
foreach ($loans as $l) {
    $transactions[] = ['date' => $l['date'], 'type' => 'debit', 'label' => 'Empréstimo Recebido', 'amount' => $l['amount'], 'icon' => 'bi-arrow-down-circle text-info'];
    
    $repayments = $db->fetchAll("SELECT paid_date as date, amount FROM loan_repayments WHERE loan_id = ?", [$l['id']]);
    foreach ($repayments as $r) {
        $transactions[] = ['date' => $r['date'], 'type' => 'credit', 'label' => 'Reembolso Empréstimo', 'amount' => $r['amount'], 'icon' => 'bi-arrow-up-circle text-success'];
    }
}

usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']));
?>

<div class="table-card">
    <div class="card-header">
        <h6 class="mb-0">Extracto de Movimentos</h6>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

