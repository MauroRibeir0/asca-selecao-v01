<?php
require_once __DIR__ . '/../../config/session.php';
requireLogin();
requireRole(ROLE_MEMBER);

$pageTitle = 'Meu Perfil';
$currentPage = 'meu_perfil';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$db = Database::getInstance();
$memberId = $_SESSION['member_id'];
$member = $db->fetch("SELECT * FROM members WHERE id = ?", [$memberId]);
$user = $db->fetch("SELECT id, username, email FROM users WHERE member_id = ?", [$memberId]);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-person-gear me-2"></i>Meu Perfil</h1>
        <p class="text-muted mb-0">Actualize os seus dados pessoais e a sua palavra-passe</p>
    </div>
</div>

<!-- Personal Data Card -->
<div class="card mb-4">
    <div class="card-body">
        <h6><i class="bi bi-person me-2"></i>Dados Pessoais</h6>
        <hr>
        <form id="formDadosPessoais">
            <input type="hidden" name="action" value="update_profile">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" class="form-control" name="full_name" value="<?= sanitize($member['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="phone" value="<?= sanitize($member['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= sanitize($member['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control" name="address" value="<?= sanitize($member['address'] ?? '') ?>">
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Guardar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Card -->
<div class="card mb-4">
    <div class="card-body">
        <h6><i class="bi bi-shield-lock me-2"></i>Alterar Palavra-passe</h6>
        <hr>
        <form id="formPassword">
            <input type="hidden" name="action" value="change_password">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Palavra-passe Actual</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nova Palavra-passe</label>
                    <input type="password" class="form-control" name="new_password" required minlength="6">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirmar Nova Palavra-passe</label>
                    <input type="password" class="form-control" name="confirm_password" required minlength="6">
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-key me-1"></i>Alterar Palavra-passe
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Account Info (read-only) -->
<div class="card mb-4">
    <div class="card-body">
        <h6><i class="bi bi-info-circle me-2"></i>Informações da Conta</h6>
        <hr>
        <div class="row g-3">
            <div class="col-md-4">
                <small class="text-muted d-block">Nome de Utilizador</small>
                <strong><?= sanitize($user['username'] ?? '—') ?></strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">BI / NUIT</small>
                <strong><?= sanitize($member['id_number'] ?? '—') ?></strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Data de Adesão</small>
                <strong><?= formatDate($member['join_date']) ?></strong>
            </div>
        </div>
    </div>
</div>

<script>
// Update personal data
document.getElementById('formDadosPessoais').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('<?= BASE_URL ?>/api/member_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) setTimeout(() => location.reload(), 1000);
        });
});

// Change password
document.getElementById('formPassword').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    if (fd.get('new_password') !== fd.get('confirm_password')) {
        APP.showToast('As palavras-passe não coincidem.', 'danger');
        return;
    }
    fetch('<?= BASE_URL ?>/api/member_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) this.reset();
        });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

