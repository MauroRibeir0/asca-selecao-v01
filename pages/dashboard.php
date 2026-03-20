<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle  = 'Dashboard';
$pageScript = 'dashboard.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active"><?= sanitize($activeCycle['name'] ?? 'Sem Ciclo') ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <span id="lastUpdatedBadge" class="badge bg-light text-secondary border align-self-center"
              style="font-size:.7rem;display:none!important;"></span>
        <button class="btn btn-outline-secondary btn-sm" id="refreshBtn" onclick="loadDashboard()">
            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
        </button>
    </div>
</div>

<!-- Alert Banner (persistent — dismiss manually) -->
<div id="alertsContainer" class="mb-3"></div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/pages/contribuicoes.php" class="quick-action">
            <div class="qa-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-plus-circle"></i>
            </div>
            <div>
                <div class="fw-600 small">Registar Pagamento</div>
                <div class="text-muted" style="font-size:.75rem;">Mensalidade</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/pages/emprestimos.php?action=new" class="quick-action">
            <div class="qa-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-bank"></i>
            </div>
            <div>
                <div class="fw-600 small">Novo Empréstimo</div>
                <div class="text-muted" style="font-size:.75rem;">Desembolsar</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/pages/emprestimos.php?tab=repayments" class="quick-action">
            <div class="qa-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-credit-card"></i>
            </div>
            <div>
                <div class="fw-600 small">Registar Reembolso</div>
                <div class="text-muted" style="font-size:.75rem;">Empréstimo</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/pages/membros.php" class="quick-action">
            <div class="qa-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-gem"></i>
            </div>
            <div>
                <div class="fw-600 small">Registar Jóia</div>
                <div class="text-muted" style="font-size:.75rem;">Adesão de Membro</div>
            </div>
        </a>
    </div>
</div>

<!-- KPI Row 1: Core financial health -->
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-primary fade-in stagger-1">
            <div class="kpi-icon"><i class="bi bi-people"></i></div>
            <div class="kpi-value" id="kpiMembers">—</div>
            <div class="kpi-label">Membros Activos</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-success fade-in stagger-2">
            <div class="kpi-icon"><i class="bi bi-safe"></i></div>
            <div class="kpi-value" id="kpiFund">—</div>
            <div class="kpi-label">Fundo Total Acumulado</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-success fade-in stagger-3">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-value" id="kpiAvailable">—</div>
            <div class="kpi-label">Capital Disponível</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-info fade-in stagger-4">
            <div class="kpi-icon"><i class="bi bi-arrow-up-right-circle"></i></div>
            <div class="kpi-value" id="kpiLoaned">—</div>
            <div class="kpi-label">Dívida Activa</div>
        </div>
    </div>
</div>

<!-- KPI Row 2: Earnings & risk -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-danger fade-in stagger-5">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-value" id="kpiOverdue">—</div>
            <div class="kpi-label">Empréstimos em Atraso</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-warning fade-in stagger-6">
            <div class="kpi-icon"><i class="bi bi-percent"></i></div>
            <div class="kpi-value" id="kpiInterest">—</div>
            <div class="kpi-label">Juros Cobrados</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-warning fade-in stagger-7">
            <div class="kpi-icon"><i class="bi bi-receipt"></i></div>
            <div class="kpi-value" id="kpiLateFees">—</div>
            <div class="kpi-label">Total de Multas</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-primary fade-in stagger-8">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-value" id="kpiRecovery">—</div>
            <div class="kpi-label">Taxa de Recuperação</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="chart-card">
            <h6><i class="bi bi-bar-chart me-2"></i>Contribuições vs Empréstimos (Mensal)</h6>
            <div class="chart-wrapper">
                <canvas id="chartContribVsLoans"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card">
            <h6><i class="bi bi-pie-chart me-2"></i>Composição do Fundo</h6>
            <div class="chart-wrapper">
                <canvas id="chartDistribution"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Upcoming Due + Recent Activity -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Obrigações Pendentes</h6>
                <small class="text-muted" id="upcomingCount"></small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th class="text-end">Valor</th>
                            <th>Vencimento</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody id="upcomingDueTable">
                        <tr><td colspan="5" class="text-center text-muted py-3">A carregar...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Actividade Recente</h6>
            </div>
            <div class="p-3" id="recentActivityList" style="max-height:340px;overflow-y:auto;">
                <div class="text-center text-muted py-3">A carregar...</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ WHATSAPP PREVIEW MODAL ══════ -->
<div class="modal fade" id="waPreviewModal" tabindex="-1" aria-labelledby="waModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;">
            <!-- Header -->
            <div class="modal-header" style="background:#25D366;color:#fff;border:none;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp fs-5"></i>
                    <div>
                        <h6 class="mb-0 fw-semibold" id="waModalLabel">Enviar via WhatsApp</h6>
                        <small id="waModalRecipientLine" style="opacity:.85;"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <!-- Body -->
            <div class="modal-body pb-2">
                <label class="form-label small fw-semibold mb-1">
                    Mensagem
                    <span class="text-muted fw-normal">(pode editar antes de enviar)</span>
                </label>
                <textarea id="waModalMessage" rows="11"
                    class="form-control"
                    style="font-family:'Segoe UI',sans-serif;font-size:.85rem;background:#f0faf0;
                           border:1px solid #25D366;border-left:4px solid #25D366;resize:vertical;"></textarea>
                <div class="mt-2 d-flex justify-content-end">
                    <button class="btn btn-link btn-sm text-muted p-0 text-decoration-none" id="waModalResetBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Repor original
                    </button>
                </div>
            </div>
            <!-- Footer -->
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-outline-secondary btn-sm" id="waModalCopyBtn">
                    <i class="bi bi-clipboard me-1"></i>Copiar
                </button>
                <button class="btn btn-sm px-4 fw-semibold" id="waModalSendBtn"
                        style="background:#25D366;color:#fff;border:none;">
                    <i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════ ALERT DETAIL MODALS ══════ -->

<!-- Modal: Empréstimos em Atraso -->
<div class="modal fade" id="modalOverdueLoans" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:#dc2626;color:#fff;">
                <h6 class="modal-title fw-semibold"><i class="bi bi-exclamation-circle me-2"></i>Empréstimos em Atraso</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover table-sm mb-0" id="tblOverdue">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th class="text-end">Valor</th>
                            <th>Vencimento</th>
                            <th class="text-center">Dias em Atraso</th>
                            <th class="text-center">WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody id="modalOverdueBody">
                        <tr><td colspan="5" class="text-center text-muted py-3">A carregar...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer border-0 bg-light">
                <small class="text-muted me-auto">Total em atraso: <span id="modalOverdueTotal" class="fw-bold text-danger">—</span></small>
                <a href="emprestimos.php" class="btn btn-sm btn-outline-secondary">Ir para Empréstimos</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Jóias Pendentes -->
<div class="modal fade" id="modalPendingJoias" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:#d97706;color:#fff;">
                <h6 class="modal-title fw-semibold"><i class="bi bi-gem me-2"></i>Jóias Pendentes</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th class="text-end">Valor Jóia</th>
                            <th class="text-center">WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody id="modalJoiasBody">
                        <tr><td colspan="3" class="text-center text-muted py-3">A carregar...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer border-0 bg-light">
                <a href="membros.php" class="btn btn-sm btn-outline-secondary ms-auto">Ir para Membros</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Juros Pendentes -->
<div class="modal fade" id="modalPendingInterest" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:#d97706;color:#fff;">
                <h6 class="modal-title fw-semibold"><i class="bi bi-percent me-2"></i>Juros Pendentes por Cobrar</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th class="text-end">Total Pendente</th>
                            <th class="text-center">Meses</th>
                            <th class="text-center">WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody id="modalInterestBody">
                        <tr><td colspan="4" class="text-center text-muted py-3">A carregar...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer border-0 bg-light">
                <small class="text-muted me-auto">Total por cobrar: <span id="modalInterestTotal" class="fw-bold text-warning">—</span></small>
                <a href="emprestimos.php" class="btn btn-sm btn-outline-secondary">Ir para Empréstimos</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Membros com Movimentação Baixa -->
<div class="modal fade" id="modalLowMovement" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:#d97706;color:#fff;">
                <h6 class="modal-title fw-semibold"><i class="bi bi-person-exclamation me-2"></i>Membros Abaixo do Limiar de Movimentação</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-3" style="font-size:.82rem;">
                    Estes membros não atingiram o limiar mínimo de movimentação em empréstimos e <strong>não serão elegíveis</strong> para receber juros fixos na distribuição do ciclo.
                </div>
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Membro</th>
                            <th class="text-end">Movimentação</th>
                            <th class="text-end">Falta para Limiar</th>
                            <th class="text-center">Elegível</th>
                        </tr>
                    </thead>
                    <tbody id="modalLowMovBody">
                        <tr><td colspan="4" class="text-center text-muted py-3">A carregar...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer border-0 bg-light">
                <small class="text-muted me-auto">Limiar mínimo: <span id="modalLowMovThreshold" class="fw-bold">—</span></small>
                <a href="distribuicoes.php" class="btn btn-sm btn-outline-secondary">Ver Elegibilidade</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
