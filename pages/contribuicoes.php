<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Contribuições';
$pageScript = 'contribuicoes.js';
require_once __DIR__ . '/../includes/header.php';
$cycle = getActiveCycle();
require_once __DIR__ . '/../includes/sidebar.php';

// Pass cycle info to JS
$cycleStart = $cycle ? substr($cycle['start_date'], 0, 7) : '';
$cycleEnd   = $cycle ? substr($cycle['end_date'],   0, 7) : '';
$minMonthly = $cycle['min_monthly'] ?? 2000;
$maxMonthly = $cycle['max_monthly'] ?? 5000;
$isAdmin    = ($_SESSION['role'] ?? '') === ROLE_ADMIN;
?>

<script>
const CYCLE_START = '<?= $cycleStart ?>';
const CYCLE_END   = '<?= $cycleEnd ?>';
const IS_ADMIN    = <?= $isAdmin ? 'true' : 'false' ?>;
</script>

<div class="page-header">
    <div>
        <h1><i class="bi bi-cash-coin me-2"></i>Contribuições</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Contribuições</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalContribuicao" onclick="openNewContribModal()">
            <i class="bi bi-plus-circle me-1"></i>Registar Pagamento
        </button>
        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalPendentes">
            <i class="bi bi-clock me-1"></i>Ver Pendentes
        </button>
    </div>
</div>

<!-- Info cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card kpi-success bg-white">
            <div class="kpi-value" id="contribTotal">—</div>
            <div class="kpi-label">Total Contribuído</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card kpi-warning bg-white">
            <div class="kpi-value" id="contribLateFees">—</div>
            <div class="kpi-label">Total Multas</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card kpi-danger bg-white">
            <div class="kpi-value" id="contribLateCount">—</div>
            <div class="kpi-label">Pagamentos em Atraso</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <i class="bi bi-filter me-1"></i><strong>Filtros:</strong>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filterMember">
                    <option value="">Todos os Membros</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Mês</span>
                    <input type="month" class="form-control" id="filterMonth">
                    <button class="btn btn-outline-secondary" type="button" id="clearMonthFilter"><i class="bi bi-x"></i></button>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-sm btn-light" onclick="loadContributions()"><i class="bi bi-arrow-clockwise me-1"></i>Actualizar</button>
            </div>
        </div>
    </div>
</div>

<!-- Contributions Table -->
<div class="table-card">
    <div class="card-header">
        <h6 class="mb-0">Histórico de Contribuições</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="contribTable">
            <thead>
                <tr>
                    <th>Membro</th>
                    <th>Mês Ref.</th>
                    <th>Valor</th>
                    <th>Data Pagamento</th>
                    <th>Data Limite</th>
                    <th>Multa</th>
                    <th>Método</th>
                    <th>Status</th>
                    <th>Acções</th>
                </tr>
            </thead>
            <tbody id="contribTableBody">
                <tr><td colspan="9" class="text-center text-muted py-4">A carregar...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New / Edit Contribution -->
<div class="modal fade" id="modalContribuicao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contribModalTitle">Registar Contribuição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formContribuicao">
                <div class="modal-body">
                    <input type="hidden" name="action" id="contribAction" value="create">
                    <input type="hidden" name="id" id="contribId" value="">

                    <div class="mb-3">
                        <label class="form-label">Membro *</label>
                        <select class="form-select" name="member_id" id="contribMember" required>
                            <option value="">Seleccionar membro...</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Mês de Referência *</label>
                            <input type="month" class="form-control" name="reference_month" id="contribMonth"
                                   min="<?= $cycleStart ?>" max="<?= $cycleEnd ?>" required>
                            <small class="text-muted">Ciclo: <?= $cycleStart ?> a <?= $cycleEnd ?></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor (MT) *</label>
                            <input type="number" class="form-control" name="amount" id="contribAmount"
                                   min="<?= $minMonthly ?>" max="<?= $maxMonthly ?>" step="100" required>
                            <small class="text-muted">Min: <?= formatMoney($minMonthly) ?> | Max: <?= formatMoney($maxMonthly) ?></small>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Data de Pagamento *</label>
                            <input type="date" class="form-control" name="paid_date" id="contribPaidDate" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Método</label>
                            <select class="form-select" name="payment_method">
                                <option value="cash">Dinheiro</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="emola">E-mola</option>
                                <option value="mkesh">Mkesh</option>
                                <option value="conta_movel">Conta Móvel</option>
                                <option value="bank_transfer">Transferência Bancária</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Referência</label>
                        <input type="text" class="form-control" name="receipt_ref" id="contribReceipt">
                    </div>
                    <!-- Late fee preview — always shown when late -->
                    <div class="alert alert-danger d-none" id="lateFeeAlert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Pagamento Fora do Prazo!</strong><br>
                        <span id="lateFeeText"></span>
                    </div>
                    <!-- Info: within deadline -->
                    <div class="alert alert-success d-none" id="onTimeAlert">
                        <i class="bi bi-check-circle me-2"></i>
                        <span id="onTimeText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i><span id="contribSubmitLabel">Registar</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Pending Members -->
<div class="modal fade" id="modalPendentes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Membros com Pagamento Pendente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Mês de Referência</label>
                    <input type="month" class="form-control" id="pendingMonth" value="<?= date('Y-m') ?>">
                </div>
                <div id="pendingList">
                    <div class="text-muted text-center py-3">Seleccione o Mês e clique para carregar</div>
                </div>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadPending()">
                    <i class="bi bi-search me-1"></i>Verificar
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


