<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Empréstimos';
$pageScript = 'emprestimos.js';
require_once __DIR__ . '/../includes/header.php';
$cycle = getActiveCycle();
require_once __DIR__ . '/../includes/sidebar.php';

$isAdmin = ($_SESSION['role'] ?? '') === ROLE_ADMIN;
?>

<script>
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const LOAN_INTEREST_PCT = <?= $cycle['loan_interest_pct'] ?? 15 ?>;
const LOAN_REPAYMENT_DAYS = <?= $cycle['loan_repayment_days'] ?? 30 ?>;
</script>

<div class="page-header">
    <div>
        <h1><i class="bi bi-bank me-2"></i>Empréstimos</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Empréstimos</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEmprestimo" onclick="openNewLoanModal()">
            <i class="bi bi-plus-circle me-1"></i>Novo Empréstimos
        </button>
    </div>
</div>

<!-- KPI cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card kpi-info bg-white">
            <div class="kpi-value" id="loanTotal">—</div>
            <div class="kpi-label">Total Emprestado</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card kpi-success bg-white">
            <div class="kpi-value" id="loanRepaid">—</div>
            <div class="kpi-label">Total Reembolsado</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card kpi-warning bg-white">
            <div class="kpi-value" id="loanInterest">—</div>
            <div class="kpi-label">Juros Cobrados</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card kpi-danger bg-white">
            <div class="kpi-value" id="loanOverdue">—</div>
            <div class="kpi-label">Em Atraso</div>
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
                <button class="btn btn-sm btn-light" onclick="loadLoans()"><i class="bi bi-arrow-clockwise me-1"></i>Actualizar</button>
            </div>
        </div>
    </div>
</div>

<!-- Filter tabs -->
<ul class="nav nav-tabs mb-3" id="loanTabs">
    <li class="nav-item"><a class="nav-link active" href="#" data-status="">Todos</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-status="active">Activos</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-status="overdue">Em Atraso</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-status="paid">Pagos</a></li>
</ul>

<!-- Loans Table -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="loansTable">
            <thead>
                <tr>
                    <th>Membro</th>
                    <th>Capital</th>
                    <th>Desembolso</th>
                    <th>Vencimento</th>
                    <th>Reembolsado</th>
                    <th>Juros Pagos</th>
                    <th>Status</th>
                    <th>Acçàµes</th>
                </tr>
            </thead>
            <tbody id="loansTableBody">
                <tr><td colspan="8" class="text-center text-muted py-4">A carregar...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New / Edit Loan -->
<div class="modal fade" id="modalEmprestimo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loanModalTitle">Novo Empréstimos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEmprestimo">
                <div class="modal-body">
                    <input type="hidden" name="action" id="loanAction" value="create">
                    <input type="hidden" name="id" id="loanEditId" value="">
                    <div class="mb-3">
                        <label class="form-label">Membro *</label>
                        <select class="form-select" name="member_id" id="loanMember" required>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valor (MT) *</label>
                            <div class="input-group">
                                <span class="input-group-text">MT</span>
                                <input type="number" class="form-control" name="amount" id="loanAmount"
                                       min="<?= $cycle['min_loan_amount'] ?? DEFAULT_MIN_LOAN_AMOUNT ?>"
                                       step="100" required>
                            </div>
                            <small class="text-muted">Minimo: <?= formatMoney($cycle['min_loan_amount'] ?? DEFAULT_MIN_LOAN_AMOUNT) ?> MT</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de Desembolso *</label>
                            <input type="date" class="form-control" name="disbursement_date" id="loanDisbDate" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" id="loanNotes" rows="2"></textarea>
                    </div>
                    <!-- Interest preview -->
                    <div class="alert alert-info" id="interestPreview" style="display:none;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="interestPreviewText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i><span id="loanSubmitLabel">Desembolsar</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Repay Loan -->
<div class="modal fade" id="modalReembolso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Registar Reembolso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReembolso">
                <div class="modal-body">
                    <input type="hidden" name="action" value="repay">
                    <input type="hidden" name="loan_id" id="repayLoanId">

                    <!-- Loan summary info (compact) -->
                    <div class="p-2 border rounded-3 mb-3 bg-light">
                        <div class="row g-0 text-center">
                            <div class="col-4 border-end">
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Principal</small>
                                <strong id="repayPrincipal" class="text-dark small">—</strong>
                            </div>
                            <div class="col-4 border-end">
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Juros</small>
                                <strong id="repayInterest" class="text-danger small">—</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Vencimento</small>
                                <strong id="repayDueDate" class="text-dark" style="font-size: 0.75rem;">—</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue alert -->
                    <div class="alert alert-danger d-none" id="overdueAlert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Empréstimos em Atraso!</strong>
                        <div id="overdueAlertText" class="mt-1 small"></div>
                    </div>

                    <!-- Payment type (compact) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Tipo de Pagamento</label>
                        <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                            <label class="list-group-item list-group-item-action d-flex align-items-center py-2" style="cursor:pointer">
                                <input class="form-check-input me-2 mt-0" type="radio" name="payment_type" id="payTotal" value="total" checked>
                                <div>
                                    <div class="fw-bold small">Liquidar Total</div>
                                    <small class="text-muted" style="font-size:0.7rem">Capital + Juros pendentes</small>
                                </div>
                            </label>
                            <label class="list-group-item list-group-item-action d-flex align-items-center py-2" id="optInterestOnly" style="cursor:pointer">
                                <input class="form-check-input me-2 mt-0" type="radio" name="payment_type" id="payInterest" value="interest_only">
                                <div>
                                    <div class="fw-bold small">Apenas Juros</div>
                                    <small class="text-muted" style="font-size:0.7rem">Capital renova-se automaticamente</small>
                                </div>
                            </label>
                            <label class="list-group-item list-group-item-action d-flex align-items-center py-2" style="cursor:pointer">
                                <input class="form-check-input me-2 mt-0" type="radio" name="payment_type" id="payPartial" value="partial">
                                <div>
                                    <div class="fw-bold small">Parcial <span class="badge bg-warning text-dark" style="font-size:0.6rem">Ã¢°Â¥ Juros</span></div>
                                    <small class="text-muted" style="font-size:0.7rem">Saldo remanescente Ã¢ ™ novo Empréstimos</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data do Pagamento *</label>
                            <input type="date" class="form-control" name="paid_date" id="repayPaidDate" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor a Entregar (MT) *</label>
                            <div class="input-group">
                                <span class="input-group-text">MT</span>
                                <input type="number" class="form-control" name="amount" id="repayAmount" min="1" step="100" required>
                            </div>
                            <small class="text-muted" id="repayAmountHint"></small>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
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
                        <div class="col-md-6">
                            <label class="form-label">Referência / Recibo</label>
                            <input type="text" class="form-control" name="receipt_ref" placeholder="Ex: MP-12345">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" id="repaySubmitBtn">
                        <i class="bi bi-check2-circle me-1"></i>Confirmar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


