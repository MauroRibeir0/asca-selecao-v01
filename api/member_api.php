<?php
/**
 * ASCA Selecção - Member Self-Service API
 * Allows members to update their own personal data and password.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_MEMBER);

$db = Database::getInstance();
$memberId = $_SESSION['member_id'];
$userId   = $_SESSION['user_id'];
$action   = $_POST['action'] ?? '';

switch ($action) {

    // ── UPDATE PERSONAL DATA ─────────────
    case 'update_profile':
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $address  = sanitize($_POST['address'] ?? '');

        if (empty($fullName)) {
            jsonResponse(['success' => false, 'message' => 'O nome completo é obrigatório.'], 422);
        }

        // Uniqueness validation
        $unicidadeErrors = validateMemberUnicidade(['full_name' => $fullName, 'phone' => $phone, 'email' => $email, 'address' => $address], $memberId);
        if ($unicidadeErrors) jsonResponse(['success' => false, 'message' => implode(' ', $unicidadeErrors)], 422);

        // Update members table (only editable fields)
        $db->query(
            "UPDATE members SET full_name = ?, phone = ?, email = ?, address = ? WHERE id = ?",
            [$fullName, $phone, $email, $address, $memberId]
        );

        // Sync email to users table
        $db->query("UPDATE users SET full_name = ?, email = ? WHERE member_id = ?", [$fullName, $email, $memberId]);

        // Update session name
        $_SESSION['full_name'] = $fullName;

        logActivity('member_updated', 'member', $memberId, "Membro actualizou seus dados pessoais");
        jsonResponse(['success' => true, 'message' => 'Dados actualizados com sucesso.']);
        break;

    // ── CHANGE PASSWORD ──────────────────
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

        logActivity('password_changed', 'member', $memberId, "Membro alterou a sua palavra-passe");
        jsonResponse(['success' => true, 'message' => 'Palavra-passe alterada com sucesso.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
