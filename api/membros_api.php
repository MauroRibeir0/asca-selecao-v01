<?php
/**
 * ASCA Selecção - Members API
 * CRUD operations for members via AJAX
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db = Database::getInstance();
$cycle = getActiveCycle();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    // ── CHECK AVAILABILITY ───────────────
    case 'check_availability':
        $field = sanitize($_GET['field'] ?? '');
        $value = sanitize($_GET['value'] ?? '');
        $excludeId = (int)($_GET['exclude_id'] ?? 0);
        
        $allowedFields = ['full_name', 'phone', 'email', 'id_number'];
        if (!in_array($field, $allowedFields)) {
            jsonResponse(['success' => false, 'message' => 'Campo inválido.'], 400);
        }
        
        $isAvailable = isUnique('members', $field, $value, $excludeId ?: null);
        jsonResponse([
            'success' => true, 
            'available' => $isAvailable,
            'message' => $isAvailable ? 'Disponível' : 'Já registado no sistema.'
        ]);
        break;

    // ── LIST ─────────────────────────────
    case 'list':
        $cycleId = $cycle ? $cycle['id'] : 0;
        $members = $db->fetchAll(
            "SELECT m.*, mc.status as cycle_status,
                    (SELECT j.status FROM joias j WHERE j.member_id = m.id AND j.cycle_id = ? LIMIT 1) as joia_status,
                    (SELECT COALESCE(SUM(c.amount),0) FROM contributions c WHERE c.member_id = m.id AND c.cycle_id = ?) as total_contributions,
                    (SELECT COALESCE(SUM(l.amount),0) FROM loans l WHERE l.member_id = m.id AND l.cycle_id = ?) as total_loans
             FROM members m
             LEFT JOIN member_cycles mc ON m.id = mc.member_id AND mc.cycle_id = ?
             WHERE mc.cycle_id = ? AND m.status = 'active'
             ORDER BY m.full_name ASC",
            [$cycleId, $cycleId, $cycleId, $cycleId, $cycleId]
        );
        jsonResponse(['success' => true, 'data' => $members]);
        break;

    // ── LIST ALL (for selectors) ─────────
    case 'list_all':
        try {
            $cycleId = $cycle ? (int)$cycle['id'] : 0;
            
            // 1. Get Group Totals for proportional distribution
            $groupTotals = $db->fetch(
                "SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE cycle_id = ?) +
                    (SELECT COALESCE(SUM(amount), 0) FROM joias WHERE cycle_id = ? AND status = 'paid') as total_savings,
                    (SELECT COALESCE(SUM(interest_amount), 0) FROM loan_interest WHERE cycle_id = ? AND status = 'paid') as total_interest",
                [$cycleId, $cycleId, $cycleId]
            );
            
            $groupSavings = (float)($groupTotals['total_savings'] ?: 1); // Avoid division by zero
            $groupInterest = (float)($groupTotals['total_interest'] ?: 0);

            // 2. Fetch members with their individual savings
            $members = $db->fetchAll(
                "SELECT m.id, m.full_name, m.phone, m.email, m.status,
                    (
                        COALESCE((SELECT SUM(amount) FROM contributions WHERE member_id = m.id AND cycle_id = ?), 0) +
                        COALESCE((SELECT SUM(amount) FROM joias WHERE member_id = m.id AND cycle_id = ? AND status = 'paid'), 0)
                    ) AS total_poupado,
                    (SELECT status FROM joias WHERE member_id = m.id AND cycle_id = ? LIMIT 1) AS joia_status,
                    COALESCE((SELECT SUM(amount) FROM loans WHERE member_id = m.id AND cycle_id = ? AND status IN ('active','overdue')), 0) AS total_active_loans
                FROM members m
                JOIN member_cycles mc ON m.id = mc.member_id AND mc.cycle_id = ?
                WHERE m.status = 'active' AND mc.status = 'active'
                ORDER BY m.full_name ASC",
                [$cycleId, $cycleId, $cycleId, $cycleId, $cycleId]
            );

            // 3. Calculate earnings (share of group interest) for each member
            foreach ($members as &$m) {
                $savings = (float)$m['total_poupado'];
                $m['total_juros'] = ($savings / $groupSavings) * $groupInterest;
            }
            unset($m);

            $loanMargin = $cycle ? (float)($cycle['loan_tolerance_margin'] ?? 0) : 0;
            jsonResponse([
                'success' => true, 
                'data' => $members, 
                'loan_tolerance_margin' => $loanMargin,
                'group_interest' => $groupInterest,
                'group_savings' => $groupSavings
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao listar membros: ' . $e->getMessage()], 500);
        }
        break;

    // ── GET ONE ──────────────────────────
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $member = $db->fetch("SELECT * FROM members WHERE id = ?", [$id]);
        if (!$member) jsonResponse(['success' => false, 'message' => 'Membro não encontrado.'], 404);
        jsonResponse(['success' => true, 'data' => $member]);
        break;

    // ── CREATE ───────────────────────────
    case 'create':
        $errors = validateRequired([
            'full_name' => 'Nome Completo',
            'join_date' => 'Data de Adesão',
        ]);
        if ($errors) jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);

        // Uniqueness validation
        $unicidadeErrors = validateMemberUnicidade($_POST);
        if ($unicidadeErrors) jsonResponse(['success' => false, 'message' => implode(' ', $unicidadeErrors)], 422);

        $fullName = sanitize($_POST['full_name']);
        $phone    = sanitize($_POST['phone'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $idNumber = sanitize($_POST['id_number'] ?? '');
        $address  = sanitize($_POST['address'] ?? '');
        $joinDate = $_POST['join_date'];
        $notes    = sanitize($_POST['notes'] ?? '');

        try {
            $db->getConnection()->beginTransaction();

            // Create member
            $db->query(
                "INSERT INTO members (full_name, phone, email, id_number, address, join_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$fullName, $phone, $email, $idNumber, $address, $joinDate, $notes]
            );
            $memberId = $db->lastInsertId();

            // Enroll in active cycle
            if ($cycle) {
                $db->query(
                    "INSERT INTO member_cycles (member_id, cycle_id, enrolled_at) VALUES (?, ?, ?)",
                    [$memberId, $cycle['id'], $joinDate]
                );
                // Create pending jóia
                $db->query(
                    "INSERT INTO joias (member_id, cycle_id, amount) VALUES (?, ?, ?)",
                    [$memberId, $cycle['id'], $cycle['joia_amount']]
                );
            }

            // Create user account for member
            $username = strtolower(str_replace(' ', '.', $fullName));
            $username = preg_replace('/[^a-z0-9.]/', '', $username);
            // Ensure unique username
            $existing = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existing) {
                $username .= $memberId;
            }
            $defaultPassword = password_hash('membro123', PASSWORD_DEFAULT);
            $db->query(
                "INSERT INTO users (username, password, full_name, email, role, member_id) VALUES (?, ?, ?, ?, 'member', ?)",
                [$username, $defaultPassword, $fullName, $email, $memberId]
            );

            $db->getConnection()->commit();

            logActivity('member_created', 'member', $memberId, "Novo membro: {$fullName}");
            jsonResponse([
                'success' => true, 
                'message' => "Membro criado com sucesso. Login: {$username} / Senha: membro123",
                'id' => $memberId
            ]);
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            jsonResponse(['success' => false, 'message' => 'Erro ao criar membro: ' . $e->getMessage()], 500);
        }
        break;

    // ── UPDATE ───────────────────────────
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $errors = validateRequired(['full_name' => 'Nome Completo']);
        if ($errors) jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);

        // Uniqueness validation
        $unicidadeErrors = validateMemberUnicidade($_POST, $id);
        if ($unicidadeErrors) jsonResponse(['success' => false, 'message' => implode(' ', $unicidadeErrors)], 422);

        $db->query(
            "UPDATE members SET full_name = ?, phone = ?, email = ?, id_number = ?, address = ?, status = ?, notes = ? WHERE id = ?",
            [
                sanitize($_POST['full_name']),
                sanitize($_POST['phone'] ?? ''),
                sanitize($_POST['email'] ?? ''),
                sanitize($_POST['id_number'] ?? ''),
                sanitize($_POST['address'] ?? ''),
                $_POST['status'] ?? 'active',
                sanitize($_POST['notes'] ?? ''),
                $id
            ]
        );
        logActivity('member_updated', 'member', $id, "Membro actualizado: " . $_POST['full_name']);
        jsonResponse(['success' => true, 'message' => 'Membro actualizado com sucesso.']);
        break;

    // ── PAY JOIA ─────────────────────────
    case 'pay_joia':
        $memberId = (int)($_POST['member_id'] ?? 0);
        $cycleId  = $cycle ? $cycle['id'] : 0;
        $paidDate = $_POST['paid_date'] ?? date('Y-m-d');

        $db->query(
            "UPDATE joias SET status = 'paid', paid_date = ?, receipt_ref = ? WHERE member_id = ? AND cycle_id = ?",
            [$paidDate, sanitize($_POST['receipt_ref'] ?? ''), $memberId, $cycleId]
        );
        logActivity('joia_paid', 'member', $memberId, "Jóia paga");
        jsonResponse(['success' => true, 'message' => 'Jóia registada como paga.']);
        break;

    // ── DELETE ───────────────────────────
    case 'delete':
        if (!isAdmin()) jsonResponse(['success' => false, 'message' => 'Apenas o admin pode eliminar membros.'], 403);
        
        $id = (int)($_POST['id'] ?? 0);
        $password = $_POST['admin_password'] ?? '';
        
        if (empty($password)) {
            jsonResponse(['success' => false, 'message' => 'A senha de administrador é obrigatória.'], 400);
        }
        
        // Validate admin password
        $adminId = $_SESSION['user_id'];
        $adminHash = $db->fetch("SELECT password FROM users WHERE id = ?", [$adminId])['password'];
        
        if (!password_verify($password, $adminHash)) {
            jsonResponse(['success' => false, 'message' => 'Senha de administrador incorrecta.'], 403);
        }
        
        try {
            $db->getConnection()->beginTransaction();
            
            // Delete user login explicitly before the member row
            $db->query("DELETE FROM users WHERE member_id = ?", [$id]);
            
            // Due to ON DELETE CASCADE on all tables, this will permanently remove loans, contributions, joias, etc.
            $db->query("DELETE FROM members WHERE id = ?", [$id]);
            
            $db->getConnection()->commit();
            
            logActivity('member_deleted', 'member', $id, "Membro apagado permanentemente (Hard Delete)");
            jsonResponse(['success' => true, 'message' => 'Membro e todo o seu histórico foram apagados definitivamente.']);
            
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            jsonResponse(['success' => false, 'message' => 'Erro ao eliminar membro: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
