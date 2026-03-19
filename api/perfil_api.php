<?php
/**
 * ASCA Selecção - User Profile API
 * Handles profile updates for Admins and Users.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// This API is specifically for NON-members (Admins/Users)
// Members use member_api.php
if (hasRole(ROLE_MEMBER)) {
    jsonResponse(['success' => false, 'message' => 'Membros devem usar a API de membro'], 403);
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');

        if (empty($fullName)) {
            jsonResponse(['success' => false, 'message' => 'O nome completo é obrigatório.'], 422);
        }

        $db->query(
            "UPDATE users SET full_name = ?, email = ? WHERE id = ?",
            [$fullName, $email, $userId]
        );

        // Update session
        $_SESSION['full_name'] = $fullName;

        logActivity('user_profile_updated', 'user', $userId, "Utilizador actualizou os dados do perfil");
        jsonResponse(['success' => true, 'message' => 'Perfil actualizado com sucesso.']);
        break;

    case 'change_password':
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            jsonResponse(['success' => false, 'message' => 'Todos os campos são obrigatórios.'], 422);
        }

        if ($newPassword !== $confirmPassword) {
            jsonResponse(['success' => false, 'message' => 'As palavras-passe não coincidem.'], 422);
        }

        if (strlen($newPassword) < 6) {
            jsonResponse(['success' => false, 'message' => 'A nova palavra-passe deve ter pelo menos 6 caracteres.'], 422);
        }

        // Verify current password
        $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Palavra-passe actual incorrecta.'], 422);
        }

        // Update password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);

        logActivity('user_password_changed', 'user', $userId, "Utilizador alterou a palavra-passe");
        jsonResponse(['success' => true, 'message' => 'Palavra-passe alterada com sucesso.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
