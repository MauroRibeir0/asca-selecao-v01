<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
requireRole(ROLE_MEMBER);

$pageTitle = 'Meus Empréstimos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$db = Database::getInstance();
$memberId = $_SESSION['member_id'];
$cycle = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;

$loans = $db->fetchAll(
    "SELECT l.*, 
            (SELECT COALESCE(SUM(lr.amount),0) FROM loan_repayments lr WHERE lr.loan_id = l.id) as total_repaid,
            (SELECT COALESCE(SUM(li.interest_amount),0) FROM loan_interest li WHERE li.loan_id = l.id AND li.status = 'paid') as total_interest
     FROM loans l WHERE l.member_id = ? AND l.cycle_id = ? ORDER BY l.disbursement_date DESC",
    [$memberId, $cycleId]
);
?>

<div class="page-header">
    <h1><i class="bi bi-bank me-2"></i>Meus Empréstimos</h1>
</div>

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
        // Repayment history
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

