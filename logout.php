<?php
/**
 * ASCA Selecção - Logout
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    logActivity('logout', 'user', $_SESSION['user_id'], 'Logout');
}
destroySession();
header('Location: ' . BASE_URL . '/login.php');
exit;
