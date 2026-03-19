<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../includes/functions.php';
$currentUser = currentUser();

// Redirect members to their specific portal page
if ($currentUser['role'] === ROLE_MEMBER) {
    redirect(BASE_URL . '/pages/member/meu_perfil.php');
}

$pageTitle = 'Meu Perfil';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = Database::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$currentUser['id']]);
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-person-gear me-2"></i>Meu Perfil</h1>
        <p class="text-muted mb-0">Gerencie suas informações de conta e palavra-passe</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Personal Data Card -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title mb-4"><i class="bi bi-person me-2"></i>Dados de Utilizador</h6>
                <form id="formDadosPerfil">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" name="full_name" value="<?= sanitize($user['full_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= sanitize($user['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome de Utilizador (Não editável)</label>
                        <input type="text" class="form-control bg-light" value="<?= sanitize($user['username']) ?>" readonly>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Actualizar Perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Password Card -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title mb-4"><i class="bi bi-shield-lock me-2"></i>Alterar Palavra-passe</h6>
                <form id="formPasswordPerfil">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Palavra-passe Actual</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova Palavra-passe</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nova Palavra-passe</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-1"></i>Alterar Palavra-passe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update Profile
    const formDados = document.getElementById('formDadosPerfil');
    if (formDados) {
        formDados.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('<?= BASE_URL ?>/api/perfil_api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    APP.showToast(data.message, data.success ? 'success' : 'danger');
                    if (data.success) {
                        // Reload to update navbar names immediately
                        setTimeout(() => location.reload(), 1000);
                    }
                });
        });
    }

    // Update Password
    const formPass = document.getElementById('formPasswordPerfil');
    if (formPass) {
        formPass.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            if (fd.get('new_password') !== fd.get('confirm_password')) {
                APP.showToast('As palavras-passe não coincidem.', 'danger');
                return;
            }
            
            fetch('<?= BASE_URL ?>/api/perfil_api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    APP.showToast(data.message, data.success ? 'success' : 'danger');
                    if (data.success) {
                        this.reset();
                    }
                });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

