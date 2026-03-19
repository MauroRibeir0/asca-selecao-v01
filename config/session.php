<?php
/**
 * ASCA Selecção - Session Management
 */
if (session_status() === PHP_SESSION_NONE) {
    // Basic session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // In production, we might want to ensure sessions don't expire too quickly
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    
    session_start();
}

require_once __DIR__ . '/constants.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data from session
 */
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'member_id' => $_SESSION['member_id'] ?? null,
    ];
}

/**
 * Check if user has a specific role
 */
function hasRole(string $role): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === $role;
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    return hasRole(ROLE_ADMIN);
}

/**
 * Check if user is admin or operator
 */
function isAdminOrUser(): bool {
    return isAdmin() || hasRole(ROLE_USER);
}

/**
 * Require authentication — redirect to login if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Sessão expirada. Por favor, faça login.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Require specific role(s)
 */
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        $_SESSION['flash_error'] = 'Acesso negado. Permissões insuficientes.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Set user session data after login
 */
function setUserSession(array $user): void {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['member_id'] = $user['member_id'] ?? null;
}

/**
 * Destroy session (logout)
 */
function destroySession(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get & store the selected cycle ID
 */
function getSelectedCycleId(): ?int {
    if (isset($_GET['cycle_id'])) {
        $_SESSION['selected_cycle_id'] = (int)$_GET['cycle_id'];
    }
    return $_SESSION['selected_cycle_id'] ?? null;
}
