<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle  = 'Relatórios';
$pageScript = 'relatorios.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db      = Database::getInstance();
$cycle   = getActiveCycle();
$cycleId = $cycle ? $cycle['id'] : 0;

// Members enrolled in active cycle
$members = $db->fetchAll(
    "SELECT m.id, m.full_name, m.phone
     FROM members m JOIN member_cycles mc ON m.id = mc.member_id
     WHERE mc.cycle_id = ? AND mc.status = 'active'
     ORDER BY m.full_name",
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

<!-- ══════ SECTION: REPORT CARDS ══════ -->
<h6 class="text-muted text-uppercase fw-semibold mb-3" style="font-size:.7rem;letter-spacing:.08em;">
    <i class="bi bi-file-earmark-text me-1"></i>Relatórios para Impressão / PDF
</h6>
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
                <p class="text-muted small">Contribuições, empréstimos, juros, reembolsos e jóia de um membro.</p>
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
                <p class="text-muted small">Contribuições, empréstimos, reembolsos e juros de um mês.</p>
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
                <p class="text-muted small">Lista de empréstimos com saldo e filtro por estado.</p>
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
                <p class="text-muted small">Resumo financeiro completo: entradas, dívida activa e saldo em caixa.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="balance">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <button class="btn btn-danger btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Elegibilidade -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h6 class="mb-0">Elegibilidade para Distribuição</h6>
                </div>
                <p class="text-muted small">Quem atingiu o limiar de movimentação e é elegível para receber juros fixos.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="eligibility_report">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <button class="btn btn-success btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Estado das Jóias -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-gem"></i>
                    </div>
                    <h6 class="mb-0">Estado das Jóias</h6>
                </div>
                <p class="text-muted small">Situação do pagamento da jóia de adesão por membro.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="joia_status">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <button class="btn btn-warning btn-sm w-100 text-dark"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Relatório de Distribuição -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-gift"></i>
                    </div>
                    <h6 class="mb-0">Relatório de Distribuição</h6>
                </div>
                <p class="text-muted small">Juros, excedente e multas distribuídos a cada membro no fim do ciclo.</p>
                <form action="<?= BASE_URL ?>/api/reports_api.php" method="GET" target="_blank">
                    <input type="hidden" name="report" value="distribution_report">
                    <input type="hidden" name="cycle_id" value="<?= $cycleId ?>">
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-file-earmark-pdf me-1"></i>Gerar Relatório</button>
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
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_members&cycle_id=<?= $cycleId ?>"
                       class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Membros</a>
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_contributions&cycle_id=<?= $cycleId ?>"
                       class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Contribuições</a>
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_loans&cycle_id=<?= $cycleId ?>"
                       class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Empréstimos</a>
                    <a href="<?= BASE_URL ?>/api/reports_api.php?report=csv_joias&cycle_id=<?= $cycleId ?>"
                       class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Jóias</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ SECTION: WHATSAPP ══════ -->
<h6 class="text-muted text-uppercase fw-semibold mb-3 mt-5" style="font-size:.7rem;letter-spacing:.08em;">
    <i class="bi bi-whatsapp me-1"></i>Mensagens WhatsApp
</h6>
<div class="row g-4">

    <!-- Individual message -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon me-3" style="background:rgba(37,211,102,.12);color:#25D366;">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Mensagem Individual</h6>
                        <small class="text-muted">Gere e envie um resumo financeiro para um membro</small>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Membro</label>
                        <select class="form-select" id="waMembers">
                            <option value="">Seleccionar membro...</option>
                            <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>" data-phone="<?= sanitize($m['phone'] ?? '') ?>">
                                <?= sanitize($m['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Tipo de Mensagem</label>
                        <select class="form-select" id="waMsgType">
                            <option value="account_summary">Resumo de Conta</option>
                            <option value="payment_reminder">Lembrete de Pagamento</option>
                            <option value="debt_balance">Saldo Devedor</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-sm px-4" id="waPreviewBtn"
                                style="background:#25D366;color:#fff;border:none;" disabled>
                            <i class="bi bi-eye me-1"></i>Pré-visualizar
                        </button>
                    </div>
                </div>

                <!-- Preview / edit area -->
                <div id="waPreviewArea" class="mt-3" style="display:none;">
                    <label class="form-label fw-semibold">
                        Mensagem <small class="text-muted fw-normal">(pode editar antes de enviar)</small>
                    </label>
                    <textarea id="waMessagePreview" rows="11"
                        class="form-control font-monospace"
                        style="font-size:.82rem;background:#f0faf0;border:1px solid #25D366;border-left:4px solid #25D366;resize:vertical;"></textarea>
                    <div class="d-flex gap-2 mt-3 flex-wrap">
                        <button class="btn btn-sm px-3" id="waSendBtn"
                                style="background:#25D366;color:#fff;border:none;" disabled>
                            <i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="waCopyBtn" disabled>
                            <i class="bi bi-clipboard me-1"></i>Copiar Texto
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="waResetBtn" style="display:none;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Repor Original
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk reminders -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="kpi-icon me-3" style="background:rgba(37,211,102,.12);color:#25D366;">
                        <i class="bi bi-send"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Lembretes em Massa</h6>
                        <small class="text-muted">Enviar lembrete de pagamento a membros com dívida</small>
                    </div>
                </div>
                <p class="text-muted small mb-3">
                    Clique em <strong>Carregar Lista</strong> para ver todos os membros com empréstimos ou juros
                    em aberto e abrir o WhatsApp para cada um.
                </p>
                <button class="btn btn-sm w-100 mb-3" id="waBulkLoadBtn"
                        style="background:#25D366;color:#fff;border:none;">
                    <i class="bi bi-people me-1"></i>Carregar Lista de Devedores
                </button>
                <div id="waBulkList" style="display:none;max-height:320px;overflow-y:auto;">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
