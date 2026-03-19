<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Membros';
$pageScript = 'membros.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-people me-2"></i>Membros</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Membros</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMembro">
            <i class="bi bi-plus-circle me-1"></i>Novo Membro
        </button>
        <button class="btn btn-warning" id="btnJoiaHeader" onclick="openJoiaSelectModal()">
            <i class="bi bi-gem me-1"></i>Registar Jóia
        </button>
        <button class="btn btn-outline-secondary" onclick="exportCSV()">
            <i class="bi bi-download me-1"></i>CSV
        </button>
    </div>
</div>

<!-- Members Table -->
<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Lista de Membros — <?= sanitize($activeCycle['name'] ?? 'N/A') ?></h6>
        <span class="badge bg-primary" id="memberCount">0</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="membersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome Completo</th>
                    <th>Telefone</th>
                    <th>Jóia</th>
                    <th>Total Contribuído</th>
                    <th>Total Emprestado</th>
                    <th>Status</th>
                    <th>Acções</th>
                </tr>
            </thead>
            <tbody id="membersTableBody">
                <tr><td colspan="8" class="text-center text-muted py-4">A carregar...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create/Edit Member -->
<div class="modal fade" id="modalMembro" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMembroTitle">Novo Membro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMembro">
                <div class="modal-body">
                    <input type="hidden" name="id" id="membroId">
                    <input type="hidden" name="action" id="membroAction" value="create">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="full_name" id="membroName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="phone" id="membroPhone" placeholder="+258 84 xxx xxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="membroEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BI / NUIT</label>
                            <input type="text" class="form-control" name="id_number" id="membroIdNumber">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de Adesão *</label>
                            <input type="date" class="form-control" name="join_date" id="membroJoinDate" required>
                        </div>
                        <div class="col-md-6" id="statusField" style="display:none;">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="membroStatus">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspenso</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="address" id="membroAddress">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" name="notes" id="membroNotes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveMembro">
                        <i class="bi bi-check-circle me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Pay Jóia -->
<div class="modal fade" id="modalJoia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registar Pagamento de Jóia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formJoia">
                <div class="modal-body">
                    <input type="hidden" name="action" value="pay_joia">
                    <input type="hidden" name="member_id" id="joiaMemberId">
                    <p>Valor da jóia: <strong><?= formatMoney($activeCycle['joia_amount'] ?? 1000) ?></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Data de Pagamento</label>
                        <input type="date" class="form-control" name="paid_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referência / Comprovativo</label>
                        <input type="text" class="form-control" name="receipt_ref">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check me-1"></i>Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Select Member for Jóia -->
<div class="modal fade" id="modalJoiaSelect" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gem me-2"></i>Seleccionar Membro para Jóia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Seleccione o membro com jóia pendente:</p>
                <select class="form-select" id="joiaSelectMember" size="8" style="min-height: 200px;">
                    <option disabled>A carregar...</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="confirmJoiaSelect()">
                    <i class="bi bi-gem me-1"></i>Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

