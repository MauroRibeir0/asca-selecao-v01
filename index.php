<?php
/**
 * ASCA Selecção - Entry Point
 */
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    if (hasRole(ROLE_MEMBER)) {
        header('Location: ' . BASE_URL . '/pages/member/meu_resumo.php');
    } else {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
    }
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
