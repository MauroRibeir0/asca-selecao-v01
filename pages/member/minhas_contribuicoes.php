<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
requireRole(ROLE_MEMBER);

$pageTitle = 'Minhas Contribuições';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$db = Database::getInstance();
$memberId = $_SESSION['member_id'];
$cycle = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;

$contributions = $db->fetchAll(
    "SELECT * FROM contributions WHERE member_id = ? AND cycle_id = ? ORDER BY reference_month DESC",
    [$memberId, $cycleId]
);

$total = array_sum(array_column($contributions, 'amount'));
$totalFees = array_sum(array_map(function($c) { return (float)$c['late_fee']; }, $contributions));
?>

<div class="page-header">
    <h1><i class="bi bi-cash-coin me-2"></i>Minhas Contribuições</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card kpi-success bg-white">
            <div class="kpi-value"><?= formatMoney($total) ?></div>
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
            <div class="kpi-value"><?= formatMoney($totalFees) ?></div>
            <div class="kpi-label">Total de Multas</div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="myContribTable">
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
                    <td><?= formatMonthName($c['reference_month']) ?></td>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


