<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Relatórios';
$pageScript = 'relatorios.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = Database::getInstance();
$cycle = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;

// Get members for selectors
$members = $db->fetchAll(
    "SELECT m.id, m.full_name, m.phone FROM members m JOIN member_cycles mc ON m.id = mc.member_id WHERE mc.cycle_id = ? AND mc.status = 'active' ORDER BY m.full_name",
    [$cycleId]
);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-file-earmark-bar-graph me-2"></i>Relatórios</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Relatórios</li>
            </ol>
        </nav>
    </div>
</div>

<!-- ══════ REPORT CARDS ══════ -->
<div class="row g-4">
    <!-- Extracto de Membro -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <h6 class="mb-0">Extracto de Membro</h6>
                </div>
                <p class="text-muted small">Extracto completo com contribuições, Empréstimos e juros.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="member_extract">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <div class="mb-3">
                        <select class="form-select" name="member_id" required>
                            <option value="">Seleccionar membro...</option>
                            <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= sanitize($m['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Relatório Mensal -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <h6 class="mb-0">Relatório Mensal</h6>
                </div>
                <p class="text-muted small">Contribuições, Empréstimos, reembolsos e juros de um Mês.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="monthly_report">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <div class="mb-3">
                        <input type="month" class="form-control" name="month" value="<?= date('Y-m') ?>" required>
                    </div>
                    <button class="btn btn-success btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Estado de Empréstimos -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-bank"></i>
                    </div>
                    <h6 class="mb-0">Estado de Empréstimos</h6>
                </div>
                <p class="text-muted small">Lista de Empréstimos com filtro por estado.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="loans_status">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <div class="mb-3">
                        <select class="form-select" name="status">
                            <option value="">Todos os Estados</option>
                            <option value="active">Activos</option>
                            <option value="overdue">Em Atraso</option>
                            <option value="paid">Pagos</option>
                        </select>
                    </div>
                    <button class="btn btn-warning btn-sm w-100 text-dark"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mapa de Contribuições -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-table"></i>
                    </div>
                    <h6 class="mb-0">Mapa de Contribuições</h6>
                </div>
                <p class="text-muted small">Tabela cruzada de membros vs meses de contribuição.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="contributions_map">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <button class="btn btn-info btn-sm w-100 text-white"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Balanço Geral -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-danger bg-opacity-10 text-danger me-3">
                        <i class="bi bi-pie-chart"></i>
                    </div>
                    <h6 class="mb-0">Balanço Geral</h6>
                </div>
                <p class="text-muted small">Resumo financeiro completo: entradas, saídas e saldo.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="balance">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <button class="btn btn-danger btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Export CSV -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary me-3">
                        <i class="bi bi-filetype-csv"></i>
                    </div>
                    <h6 class="mb-0">Exportar para CSV</h6>
                </div>
                <p class="text-muted small">Exportar dados para folha de cálculo (Excel).</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_members&cycle_id=<?= $cycleId ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Membros</a>
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_contributions&cycle_id=<?= $cycleId ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Contribuições</a>
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_loans&cycle_id=<?= $cycleId ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Empréstimos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ WHATSAPP SECTION ══════ -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon me-3" style="background: rgba(37, 211, 102, .1); color: #25D366;">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Partilhar via WhatsApp</h6>
                        <small class="text-muted">Enviar resumos financeiros directamente aos membros</small>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Membro</label>
                        <select class="form-select" id="waMembers">
                            <option value="">Seleccionar membro...</option>
                            <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>" data-phone="<?= sanitize($m['phone'] ?? '') ?>"><?= sanitize($m['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Mensagem</label>
                        <select class="form-select" id="waMsgType">
                            <option value="account_summary">ðŸ“‹ Resumo de Conta</option>
                            <option value="payment_reminder">â° Lembrete de Pagamento</option>
                            <option value="debt_balance">ðŸ“Š Saldo Devedor</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-sm w-100" id="waPreviewBtn" style="background: #25D366; color: #fff; border: none;" disabled>
                            <i class="bi bi-eye me-1"></i>Pré-visualizar
                        </button>
                    </div>
                </div>

                <!-- Preview Area -->
                <div id="waPreviewArea" class="mt-3" style="display: none;">
                    <label class="form-label fw-600">Pré-visualização da Mensagem:</label>
                    <div id="waMessagePreview" style="background: #e5ddd5; border-radius: 8px; padding: 1rem; font-family: 'Segoe UI', sans-serif; font-size: .9rem; white-space: pre-wrap; max-height: 300px; overflow-y: auto; border-left: 4px solid #25D366;"></div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm" id="waSendBtn" style="background: #25D366; color: #fff; border: none;" disabled>
                            <i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="waCopyBtn" disabled>
                            <i class="bi bi-clipboard me-1"></i>Copiar Texto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


