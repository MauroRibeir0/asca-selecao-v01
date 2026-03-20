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

<!-- Cycle Progress + Compliance Strip -->
<div class="row g-3 mb-4" id="cycleStrip" style="display:none;">
    <div class="col-md-5">
        <div class="card border-0" style="background:#f8fafc;">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="fw-semibold text-secondary">Progresso do Ciclo</small>
                    <small id="cycleDatesLabel" class="text-muted">—</small>
                </div>
                <div class="progress" style="height:7px;border-radius:4px;">
                    <div class="progress-bar bg-primary" id="cycleProgressBar"
                         role="progressbar" style="width:0%;border-radius:4px;"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small id="cycleElapsedLabel" class="text-muted" style="font-size:.7rem;"></small>
                    <small id="cyclePctLabel" class="text-primary fw-semibold" style="font-size:.7rem;"></small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0" style="background:#f8fafc;">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="fw-semibold text-secondary">Mensalidades (mês actual)</small>
                    <small id="complianceLabel" class="fw-bold text-success">—</small>
                </div>
                <div class="progress" style="height:7px;border-radius:4px;">
                    <div class="progress-bar bg-success" id="complianceBar"
                         role="progressbar" style="width:0%;border-radius:4px;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0" style="background:#fff8f0;">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="fw-semibold text-warning">Juros Pendentes</small>
                    <small id="stripPendingInterest" class="fw-bold text-warning">—</small>
                </div>
                <small class="text-muted" style="font-size:.7rem;">Por cobrar a mutuários</small>
            </div>
        </div>
    </div>
</div>

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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
