<?php
/**
 * ASCA Selecção - Users Management API
 *
 * ROLE_ADMIN only.
 * Actions: list, create, update, reset_password, toggle_active
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN);

$db     = Database::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    // ── LIST ──────────────────────────────────────────────────
    case 'list':
        $users = $db->fetchAll(
            "SELECT u.id, u.username, u.full_name, u.email, u.role, u.is_active, u.member_id,
                    u.created_at,
                    (SELECT al.created_at
                     FROM activity_log al
                     WHERE al.user_id = u.id AND al.action = 'login'
                     ORDER BY al.created_at DESC LIMIT 1) AS last_login
             FROM users u
             ORDER BY u.full_name"
        );
        jsonResponse(['success' => true, 'data' => $users]);
        break;

    // ── CREATE ────────────────────────────────────────────────
    case 'create':
        $username  = trim($_POST['username']  ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $role      = trim($_POST['role']      ?? '');
        $memberId  = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $isActive  = isset($_POST['is_active']) ? (int)(bool)$_POST['is_active'] : 1;

        // Validation
        $errors = [];
        if (empty($username))  $errors[] = 'O nome de utilizador é obrigatório.';
        if (empty($fullName))  $errors[] = 'O nome completo é obrigatório.';
        if (strlen($password) < 6) $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
        if (!in_array($role, ['admin', 'user', 'member'], true)) {
            $errors[] = 'Papel inválido. Use admin, user ou member.';
        }
        if ($role === 'member' && !$memberId) {
            $errors[] = 'Para o papel "member", é necessário associar um membro.';
        }
        if ($errors) jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);

        // Username uniqueness
        $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            jsonResponse(['success' => false, 'message' => 'Este nome de utilizador já existe.'], 422);
        }

        // Member exists (if role=member)
        if ($role === 'member' && $memberId) {
            $member = $db->fetch("SELECT id FROM members WHERE id = ?", [$memberId]);
            if (!$member) {
                jsonResponse(['success' => false, 'message' => 'Membro não encontrado.'], 422);
            }
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO users (username, full_name, email, password, role, is_active, member_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$username, $fullName, $email, $hashed, $role, $isActive, $memberId]
        );
        $newId = (int)$db->lastInsertId();

        logActivity('user_created', 'user', $newId, "Utilizador criado: {$username} ({$role})");
        jsonResponse(['success' => true, 'message' => "Utilizador '{$username}' criado com sucesso.", 'id' => $newId]);
        break;

    // ── UPDATE ────────────────────────────────────────────────
    case 'update':
        $id       = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email']     ?? '');
        $role     = trim($_POST['role']      ?? '');
        $memberId = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $isActive = isset($_POST['is_active']) ? (int)(bool)$_POST['is_active'] : 1;

        if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 422);

        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) jsonResponse(['success' => false, 'message' => 'Utilizador não encontrado.'], 404);

        // Validation
        $errors = [];
        if (empty($fullName)) $errors[] = 'O nome completo é obrigatório.';
        if (!in_array($role, ['admin', 'user', 'member'], true)) {
            $errors[] = 'Papel inválido.';
        }
        if ($role === 'member' && !$memberId) {
            $errors[] = 'Para o papel "member", é necessário associar um membro.';
        }
        if ($errors) jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);

        // Member exists (if role=member)
        if ($role === 'member' && $memberId) {
            $member = $db->fetch("SELECT id FROM members WHERE id = ?", [$memberId]);
            if (!$member) {
                jsonResponse(['success' => false, 'message' => 'Membro não encontrado.'], 422);
            }
        }

        $db->query(
            "UPDATE users SET full_name=?, email=?, role=?, is_active=?, member_id=? WHERE id=?",
            [$fullName, $email, $role, $isActive, $memberId, $id]
        );

        logActivity('user_updated', 'user', $id, "Utilizador actualizado: {$user['username']} → role={$role}, active={$isActive}");
        jsonResponse(['success' => true, 'message' => 'Utilizador actualizado com sucesso.']);
        break;

    // ── RESET PASSWORD ────────────────────────────────────────
    case 'reset_password':
        $id          = (int)($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 422);
        if (strlen($newPassword) < 6) {
            jsonResponse(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres.'], 422);
        }

        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) jsonResponse(['success' => false, 'message' => 'Utilizador não encontrado.'], 404);

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password=? WHERE id=?", [$hashed, $id]);

        logActivity('password_reset', 'user', $id, "Senha redefinida para o utilizador: {$user['username']}");
        jsonResponse(['success' => true, 'message' => "Senha do utilizador '{$user['username']}' redefinida com sucesso."]);
        break;

    // ── TOGGLE ACTIVE ─────────────────────────────────────────
    case 'toggle_active':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID inválido.'], 422);

        // Cannot deactivate own account
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($id === $currentUserId) {
            jsonResponse(['success' => false, 'message' => 'Não pode desactivar a sua própria conta.'], 422);
        }

        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) jsonResponse(['success' => false, 'message' => 'Utilizador não encontrado.'], 404);

        $newStatus = $user['is_active'] ? 0 : 1;
        $db->query("UPDATE users SET is_active=? WHERE id=?", [$newStatus, $id]);

        $statusLabel = $newStatus ? 'activado' : 'desactivado';
        logActivity('user_toggle_active', 'user', $id, "Utilizador {$statusLabel}: {$user['username']}");
        jsonResponse([
            'success'   => true,
            'message'   => "Utilizador '{$user['username']}' {$statusLabel} com sucesso.",
            'is_active' => $newStatus,
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
