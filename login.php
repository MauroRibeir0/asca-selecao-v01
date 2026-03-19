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
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="login-page">

    <!-- Left Panel — Branding -->
    <div class="login-left">
        <div class="login-brand-icon">
            <i class="bi bi-bank2"></i>
        </div>
        <h1>ASCA Selecção</h1>
        <p>Sistema de Gestão de Poupança e Empréstimo — Gerencie contribuições, empréstimos e juros do seu grupo de forma simples e transparente.</p>

        <div class="login-features">
            <span class="login-feature-item">
                <i class="bi bi-shield-check"></i> Seguro
            </span>
            <span class="login-feature-item">
                <i class="bi bi-lightning-charge"></i> Rápido
            </span>
            <span class="login-feature-item">
                <i class="bi bi-graph-up"></i> Transparente
            </span>
        </div>
    </div>

    <!-- Right Panel — Form -->
    <div class="login-right">
        <div class="login-card fade-in">
            <h2>Bem-vindo</h2>
            <p class="subtitle">Faça login para aceder ao sistema</p>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert" style="border-radius:10px; font-size:.875rem;">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
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
                               placeholder="Introduza o seu utilizador"
                               value="<?= sanitize($username ?? '') ?>"
                               required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Palavra-passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Introduza a sua palavra-passe"
                               required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1" aria-label="Mostrar/ocultar palavra-passe">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-login mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted" style="font-size:.75rem;">&copy; <?= date('Y') ?> <?= APP_NAME ?> &middot; v<?= APP_VERSION ?></small>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const input = document.getElementById('password');
    const icon  = this.querySelector('i');
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
