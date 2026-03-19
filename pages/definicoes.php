<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN);

$pageTitle = 'Definições';
$pageScript = 'definicoes.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = Database::getInstance();
$cycle = getActiveCycle();

// Save settings via AJAX handled by definicoes.js
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-gear me-2"></i>Definições</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Definições do Ciclo</li>
            </ol>
        </nav>
    </div>
    <?php if ($cycle): ?>
    <div>
        <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i><?= sanitize($cycle['name']) ?></span>
    </div>
    <?php endif; ?>
</div>

<?php if ($cycle): ?>
<form id="formDefinicoes" method="POST">

    <!-- ═══════════ Section 1: Cycle Information ═══════════ -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calendar3"></i>
            </div>
            <div>
                <h5 class="mb-0">Informação do Ciclo</h5>
                <small class="text-muted">Dados gerais e período de vigência do ciclo</small>
            </div>
        </div>
        <div class="settings-section-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nome do Ciclo *</label>
                    <input type="text" class="form-control" name="name" value="<?= sanitize($cycle['name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Data de Início *</label>
                    <input type="date" class="form-control" name="start_date" value="<?= $cycle['start_date'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Data de Fim *</label>
                    <input type="date" class="form-control" name="end_date" value="<?= $cycle['end_date'] ?>" required>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Section 2: Jóia de Adesão ═══════════ -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-gem"></i>
            </div>
            <div>
                <h5 class="mb-0">Jóia de Adesão</h5>
                <small class="text-muted">Taxa de entrada única paga pelo membro ao aderir ao ciclo</small>
            </div>
        </div>
        <div class="settings-section-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Valor da Jóia (MT)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-cash"></i></span>
                        <input type="number" class="form-control" name="joia_amount" value="<?= $cycle['joia_amount'] ?>" step="100" min="0">
                        <span class="input-group-text">MT</span>
                    </div>
                    <small class="text-muted">Valor obrigatório para o membro participar no ciclo</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Data Limite de Pagamento</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" class="form-control" name="joia_deadline" value="<?= $cycle['joia_deadline'] ?>">
                    </div>
                    <small class="text-muted">Prazo máximo para pagamento da jóia</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Section 3: Contribuições Mensais ═══════════ -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div>
                <h5 class="mb-0">Contribuições Mensais</h5>
                <small class="text-muted">Regras para as poupanças mensais obrigatórias dos membros</small>
            </div>
        </div>
        <div class="settings-section-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Contribuição Mínima (MT)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-arrow-down-circle"></i></span>
                        <input type="number" class="form-control" name="min_monthly" value="<?= $cycle['min_monthly'] ?>" step="100" min="0">
                        <span class="input-group-text">MT</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Contribuição Máxima (MT)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-arrow-up-circle"></i></span>
                        <input type="number" class="form-control" name="max_monthly" value="<?= $cycle['max_monthly'] ?>" step="100" min="0">
                        <span class="input-group-text">MT</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Dia Limite de Pagamento</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-day"></i></span>
                        <input type="number" class="form-control" name="monthly_deadline_day" value="<?= $cycle['monthly_deadline_day'] ?>" min="1" max="28">
                    </div>
                    <small class="text-muted">Dia do Mês seguinte até ao qual o pagamento é considerado no prazo</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Multa por Atraso (%)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-exclamation-triangle"></i></span>
                        <input type="number" class="form-control" name="late_fee_pct" value="<?= $cycle['late_fee_pct'] ?>" step="0.5" min="0" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Percentagem cobrada sobre o valor da contribuição quando paga fora do prazo</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Section 4: Empréstimos ═══════════ -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-bank"></i>
            </div>
            <div>
                <h5 class="mb-0">Empréstimos</h5>
                <small class="text-muted">Configurações de juros, prazos e limites para Empréstimos</small>
            </div>
        </div>
        <div class="settings-section-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Taxa de Juros (%)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-percent"></i></span>
                        <input type="number" class="form-control" name="loan_interest_pct" value="<?= $cycle['loan_interest_pct'] ?>" step="0.5" min="0" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Juros mensal cobrado sobre o capital emprestado</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Prazo de Reembolso</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-clock-history"></i></span>
                        <input type="number" class="form-control" name="loan_repayment_days" value="<?= $cycle['loan_repayment_days'] ?>" min="7" max="365">
                        <span class="input-group-text">dias</span>
                    </div>
                    <small class="text-muted">Número de dias após desembolso para vencimento</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Margem de TolerÃ¢ncia (MT)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="number" class="form-control" name="loan_tolerance_margin" value="<?= $cycle['loan_tolerance_margin'] ?? 0 ?>" step="500" min="0">
                        <span class="input-group-text">MT</span>
                    </div>
                    <small class="text-muted">Valor adicional acima do poupado que o membro pode emprestar.<br><strong>0 = só empresta até ao poupado</strong></small>
                </div>
            </div>

            <hr class="my-3">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Movimentação Mínima no Ciclo (MT)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-graph-up-arrow"></i></span>
                        <input type="number" class="form-control" name="min_loan_movement" value="<?= $cycle['min_loan_movement'] ?>" step="1000" min="0">
                        <span class="input-group-text">MT</span>
                    </div>
                    <small class="text-muted">Meta mínima de Empréstimos por membro para manter direito a juros</small>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 h-100">
                        <div>
                            <div class="fw-semibold">Permitir Múltiplos Empréstimos</div>
                            <small class="text-muted">Membros podem ter mais de um empréstimo em simultÃ¢neo</small>
                        </div>
                        <div class="form-check form-switch ms-3">
                            <input class="form-check-input fs-4" type="checkbox" role="switch" name="allow_multiple_loans" value="1" 
                                <?= ($cycle['allow_multiple_loans'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Section 5: Distribuições ═══════════ -->
    <div class="settings-section">
        <div class="settings-section-header">
            <div class="settings-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-pie-chart"></i>
            </div>
            <div>
                <h5 class="mb-0">Distribuições & Rendimentos</h5>
                <small class="text-muted">Regras para distribuição de juros e rendimentos aos membros</small>
            </div>
        </div>
        <div class="settings-section-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Direito Fixo de Juros por Membro (MT)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-trophy"></i></span>
                        <input type="number" class="form-control" name="fixed_interest_entitlement" value="<?= $cycle['fixed_interest_entitlement'] ?>" step="500" min="0">
                        <span class="input-group-text">MT</span>
                    </div>
                    <small class="text-muted">Valor fixo de juros que cada membro recebe ao final do ciclo, desde que tenha atingido a movimentação mínima</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ Save Button ═══════════ -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="reset" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
        </button>
        <button type="submit" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-check-circle me-1"></i>Guardar Alterações
        </button>
    </div>
</form>

<?php else: ?>
<div class="alert alert-warning d-flex align-items-center">
    <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
    <div>
        <strong>Nenhum ciclo activo.</strong><br>
        Crie um ciclo para poder configurar as definições do sistema.
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


