<?php
/**
 * ASCA Selecção - Login Page
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in? Redirect
if (isLoggedIn()) {
    if (hasRole(ROLE_MEMBER)) {
        redirect(BASE_URL . '/pages/member/meu_resumo.php');
    } else {
        redirect(BASE_URL . '/pages/dashboard.php');
    }
}

$error = getFlash('error');

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Preencha todos os campos.';
    } else {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = ? AND is_active = 1", [$username]);

        if ($user && password_verify($password, $user['password'])) {
            setUserSession($user);
            logActivity('login', 'user', $user['id'], "Login de {$user['full_name']}");

            if ($user['role'] === ROLE_MEMBER) {
                redirect(BASE_URL . '/pages/member/meu_resumo.php');
            } else {
                redirect(BASE_URL . '/pages/dashboard.php');
            }
        } else {
            $error = 'Credenciais inválidas.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — Entrar</title>
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <!-- Left Panel (branding) -->
    <div class="login-left">
        <i class="bi bi-bank2 mb-3" style="font-size: 3.5rem; z-index:1;"></i>
        <h1>ASCA Selecção</h1>
        <p>Sistema de Gestão de Poupança e Empréstimo — Gerencie contribuições, empréstimos e juros do seu grupo de forma simples e transparente.</p>
        <div class="mt-4" style="z-index:1;">
            <div class="d-flex gap-4 text-white-50" style="font-size:.85rem;">
                <span><i class="bi bi-shield-check me-1"></i> Seguro</span>
                <span><i class="bi bi-lightning me-1"></i> Rápido</span>
                <span><i class="bi bi-graph-up me-1"></i> Transparente</span>
            </div>
        </div>
    </div>

    <!-- Right Panel (form) -->
    <div class="login-right">
        <div class="login-card">
            <h2>Bem-vindo</h2>
            <p class="subtitle">Faça login para aceder ao sistema</p>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <div><?= sanitize($error) ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Utilizador</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Introduza o seu utilizador" value="<?= sanitize($username ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Palavra-passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Introduza a sua palavra-passe" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?> · v<?= APP_VERSION ?></small>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const input = document.getElementById('password');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
    .then(function(reg) { console.log('SW registered.', reg); })
    .catch(function(err) { console.error('SW error:', err); });
}
window.OneSignal = window.OneSignal || [];
OneSignal.push(function() {
  OneSignal.init({
    appId: "4e86da52-696c-4cd7-8545-a5e755c88162"
  });
});
</script>
</body>
</html>
