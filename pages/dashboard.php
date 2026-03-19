<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Dashboard';
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
        <button class="btn btn-outline-secondary btn-sm" onclick="loadDashboard()">
            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
        </button>
    </div>
</div>

<!-- Alert Cards (filled by JS) -->
<div id="alertsContainer"></div>

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

<!-- KPI Cards -->
<div class="row g-3 mb-4">
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
        <div class="kpi-card kpi-info fade-in stagger-3">
            <div class="kpi-icon"><i class="bi bi-arrow-up-right-circle"></i></div>
            <div class="kpi-value" id="kpiLoaned">—</div>
            <div class="kpi-label">Total Emprestado</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-warning fade-in stagger-4">
            <div class="kpi-icon"><i class="bi bi-percent"></i></div>
            <div class="kpi-value" id="kpiInterest">—</div>
            <div class="kpi-label">Total de Juros Cobrados</div>
        </div>
    </div>
</div>

<!-- Second row KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-danger fade-in stagger-5">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-value" id="kpiOverdue">—</div>
            <div class="kpi-label">Empréstimos em Atraso</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi-card kpi-success fade-in stagger-6">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-value" id="kpiAvailable">—</div>
            <div class="kpi-label">Capital Disponível</div>
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
            <h6><i class="bi bi-bar-chart me-2"></i>Entradas vs Saídas (Mensal)</h6>
            <div class="chart-wrapper">
                <canvas id="chartContribVsLoans"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card">
            <h6><i class="bi bi-pie-chart me-2"></i>Distribuição</h6>
            <div class="chart-wrapper">
                <canvas id="chartDistribution"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Upcoming Due + Alerts -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Obrigações Pendentes</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                        </tr>
                    </thead>
                    <tbody id="upcomingDueTable">
                        <tr><td colspan="4" class="text-center text-muted py-3">A carregar...</td></tr>
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
            <div class="p-3" id="recentActivityList">
                <div class="text-center text-muted py-3">A carregar...</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

