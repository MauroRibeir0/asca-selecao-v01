<?php
/**
 * ASCA Selecção - Header (included in every page)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

$currentUser = currentUser();
$activeCycle = getActiveCycle();
$allCycles   = getAllCycles();

// Determine current page
$currentPage = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Generate user initials for avatar
$_initials = '';
$_nameParts = explode(' ', trim($currentUser['full_name'] ?? 'U'));
foreach ($_nameParts as $_part) {
    $_initials .= strtoupper(substr($_part, 0, 1));
    if (strlen($_initials) >= 2) break;
}
if (empty($_initials)) $_initials = 'U';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ASCA Selecção - Sistema de Gestão de Poupança e Empréstimo">
    <title><?= APP_NAME ?> — <?= $pageTitle ?? 'Dashboard' ?></title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async></script>
    <meta name="base-url" content="<?= BASE_URL ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-navbar fixed-top px-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-link text-white d-lg-none p-1" id="sidebarToggle" aria-label="Menu">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="<?= BASE_URL ?>/pages/dashboard.php">
            <div class="brand-icon">
                <i class="bi bi-bank2"></i>
            </div>
            <span class="fw-bold"><?= APP_NAME ?></span>
        </a>
    </div>

    <div class="d-flex align-items-center ms-auto gap-2">
        <!-- Cycle Selector (admin/staff only) -->
        <?php if (isAdminOrUser() && count($allCycles) > 0): ?>
        <div class="d-none d-sm-block">
            <select class="form-select form-select-sm" id="cycleSelectorNav" style="min-width: 155px;">
                <?php foreach ($allCycles as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($activeCycle && $activeCycle['id'] == $c['id']) ? 'selected' : '' ?>>
                    <?= sanitize($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn btn-link text-white position-relative" data-bs-toggle="dropdown" aria-label="Notificações">
                <i class="bi bi-bell fs-5"></i>
                <span class="notification-badge" id="notifBadge" style="display:none;"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown" style="min-width:240px;">
                <li><h6 class="dropdown-header fw-700 px-3 py-2">Notificações</h6></li>
                <li><span class="dropdown-item text-muted small">Sem notificações</span></li>
            </ul>
        </div>

        <!-- User Menu -->
        <div class="dropdown">
            <button class="btn btn-link text-white d-flex align-items-center gap-2 text-decoration-none p-1" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= htmlspecialchars($_initials) ?></div>
                <div class="d-none d-md-block text-start">
                    <div class="fw-semibold" style="font-size:.8rem; line-height:1.2;"><?= sanitize($currentUser['full_name'] ?? 'Utilizador') ?></div>
                    <div class="text-white-50" style="font-size:.68rem;"><?= ucfirst($currentUser['role'] ?? '') ?></div>
                </div>
                <i class="bi bi-chevron-down small opacity-75"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <div class="px-3 py-2 border-bottom mb-1">
                        <div class="fw-700 small"><?= sanitize($currentUser['full_name'] ?? '') ?></div>
                        <div class="text-muted" style="font-size:.75rem;"><?= sanitize($currentUser['username'] ?? '') ?></div>
                    </div>
                </li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/meu_perfil.php"><i class="bi bi-person me-2 text-primary"></i>Meu Perfil</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="wrapper">
