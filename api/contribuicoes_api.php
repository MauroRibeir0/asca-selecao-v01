<?php
/**
 * ASCA Selecção - Contributions API
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db = Database::getInstance();
$cycle = getActiveCycle();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (!$cycle) jsonResponse(['success' => false, 'message' => 'Nenhum ciclo activo.'], 404);
$cycleId = $cycle['id'];

/**
 * Helper: validate that a reference month is within the cycle range.
 * reference_month comes as 'YYYY-MM', cycle start/end as 'YYYY-MM-DD'
 */
function isMonthInCycle(string $refMonth, array $cycle): bool {
    $refDate  = $refMonth . '-01';
    $cycleStart = substr($cycle['start_date'], 0, 7) . '-01'; // first day of start month
    $cycleEnd   = substr($cycle['end_date'],   0, 7) . '-01'; // first day of end month
    return ($refDate >= $cycleStart && $refDate <= $cycleEnd);
}

switch ($action) {

    // ── LIST ──────────────────────────────────────────────
    case 'list':
        $contributions = $db->fetchAll(
            "SELECT c.*, m.full_name
             FROM contributions c
             JOIN members m ON c.member_id = m.id
             WHERE c.cycle_id = ? AND m.status = 'active'
             ORDER BY c.paid_date DESC, m.full_name ASC",
            [$cycleId]
        );
        jsonResponse(['success' => true, 'data' => $contributions]);
        break;

    // ── GET SINGLE ────────────────────────────────────────
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $row = $db->fetch(
            "SELECT c.*, m.full_name
             FROM contributions c
             JOIN members m ON c.member_id = m.id
             WHERE c.id = ? AND c.cycle_id = ?",
            [$id, $cycleId]
        );
        if (!$row) jsonResponse(['success' => false, 'message' => 'Contribuição não encontrada.'], 404);
        jsonResponse(['success' => true, 'data' => $row]);
        break;

    // ── CREATE ────────────────────────────────────────────
    case 'create':
        $memberId  = (int)($_POST['member_id'] ?? 0);
        $amount    = (float)($_POST['amount'] ?? 0);
        $refMonth  = $_POST['reference_month'] ?? ''; // YYYY-MM
        $paidDate  = $_POST['paid_date'] ?? date('Y-m-d');
        $method    = $_POST['payment_method'] ?? 'cash';
        $receipt   = sanitize($_POST['receipt_ref'] ?? '');
        $notes     = sanitize($_POST['notes'] ?? '');

        // Validate amount range
        if ($amount < $cycle['min_monthly'] || $amount > $cycle['max_monthly']) {
            jsonResponse(['success' => false, 'message' =>
                "Valor deve estar entre " . formatMoney($cycle['min_monthly']) . " e " . formatMoney($cycle['max_monthly'])], 422);
        }

        // Validate month is within the cycle
        if (!isMonthInCycle($refMonth, $cycle)) {
            $startFmt = date('M Y', strtotime($cycle['start_date']));
            $endFmt   = date('M Y', strtotime($cycle['end_date']));
            jsonResponse(['success' => false, 'message' =>
                "O mês seleccionado está fora do ciclo vigente ({$startFmt} – {$endFmt})."], 422);
        }

        // Check duplicate for this month
        $existing = $db->fetch(
            "SELECT id FROM contributions WHERE member_id = ? AND cycle_id = ? AND reference_month = ?",
            [$memberId, $cycleId, $refMonth . '-01']
        );
        if ($existing) {
            jsonResponse(['success' => false, 'message' => 'Já existe uma contribuição registada para este membro neste mês.'], 422);
        }

        // Calculate due date and late fee (mandatory when late)
        $referenceDate = $refMonth . '-01';
        $dueDate  = calculateContributionDueDate($referenceDate);
        $isLate   = ($paidDate > $dueDate) ? 1 : 0;
        $lateFee  = $isLate ? round($amount * ($cycle['late_fee_pct'] / 100), 2) : 0.00;

        $db->query(
            "INSERT INTO contributions (member_id, cycle_id, reference_month, amount, paid_date, due_date, is_late, late_fee, payment_method, receipt_ref, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$memberId, $cycleId, $referenceDate, $amount, $paidDate, $dueDate, $isLate, $lateFee, $method, $receipt, $notes]
        );
        $newId = (int)$db->lastInsertId();

        $memberName = $db->fetch("SELECT full_name FROM members WHERE id = ?", [$memberId])['full_name'];
        logActivity('contribution_created', 'contribution', $newId,
            "Contribuição de {$memberName}: " . formatMoney($amount) . ($isLate ? " (multa: " . formatMoney($lateFee) . ")" : ""));

        $msg = 'Contribuição registada com sucesso.';
        if ($isLate) $msg .= " Multa de " . formatMoney($lateFee) . " aplicada por atraso (15%).";

        jsonResponse(['success' => true, 'message' => $msg, 'is_late' => $isLate, 'late_fee' => $lateFee]);
        break;

    // ── UPDATE ────────────────────────────────────────────
    case 'update':
        $id       = (int)($_POST['id'] ?? 0);
        $amount   = (float)($_POST['amount'] ?? 0);
        $refMonth = $_POST['reference_month'] ?? '';
        $paidDate = $_POST['paid_date'] ?? date('Y-m-d');
        $method   = $_POST['payment_method'] ?? 'cash';
        $receipt  = sanitize($_POST['receipt_ref'] ?? '');
        $notes    = sanitize($_POST['notes'] ?? '');

        // Fetch existing record
        $contrib = $db->fetch("SELECT * FROM contributions WHERE id = ? AND cycle_id = ?", [$id, $cycleId]);
        if (!$contrib) jsonResponse(['success' => false, 'message' => 'Contribuição não encontrada.'], 404);

        // Validate amount range
        if ($amount < $cycle['min_monthly'] || $amount > $cycle['max_monthly']) {
            jsonResponse(['success' => false, 'message' =>
                "Valor deve estar entre " . formatMoney($cycle['min_monthly']) . " e " . formatMoney($cycle['max_monthly'])], 422);
        }

        // Validate month is within the cycle
        if (!isMonthInCycle($refMonth, $cycle)) {
            $startFmt = date('M Y', strtotime($cycle['start_date']));
            $endFmt   = date('M Y', strtotime($cycle['end_date']));
            jsonResponse(['success' => false, 'message' =>
                "O mês seleccionado está fora do ciclo vigente ({$startFmt} – {$endFmt})."], 422);
        }

        // Check duplicate (excluding current record)
        $referenceDate = $refMonth . '-01';
        $dup = $db->fetch(
            "SELECT id FROM contributions WHERE member_id = ? AND cycle_id = ? AND reference_month = ? AND id != ?",
            [$contrib['member_id'], $cycleId, $referenceDate, $id]
        );
        if ($dup) {
            jsonResponse(['success' => false, 'message' => 'Já existe outra contribuição registada para este membro neste mês.'], 422);
        }

        // Recalculate late fee
        $dueDate = calculateContributionDueDate($referenceDate);
        $isLate  = ($paidDate > $dueDate) ? 1 : 0;
        $lateFee = $isLate ? round($amount * ($cycle['late_fee_pct'] / 100), 2) : 0.00;

        $db->query(
            "UPDATE contributions SET reference_month=?, amount=?, paid_date=?, due_date=?, is_late=?, late_fee=?, payment_method=?, receipt_ref=?, notes=?
             WHERE id=? AND cycle_id=?",
            [$referenceDate, $amount, $paidDate, $dueDate, $isLate, $lateFee, $method, $receipt, $notes, $id, $cycleId]
        );

        logActivity('contribution_updated', 'contribution', $id, "Contribuição editada: " . formatMoney($amount));

        $msg = 'Contribuição actualizada com sucesso.';
        if ($isLate) $msg .= " Multa de " . formatMoney($lateFee) . " aplicada por atraso (15%).";

        jsonResponse(['success' => true, 'message' => $msg, 'is_late' => $isLate, 'late_fee' => $lateFee]);
        break;

    // ── DELETE ────────────────────────────────────────────
    case 'delete':
        requireRole(ROLE_ADMIN); // Only admin can delete
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $contrib = $db->fetch("SELECT c.*, m.full_name FROM contributions c JOIN members m ON c.member_id = m.id WHERE c.id = ? AND c.cycle_id = ?", [$id, $cycleId]);
        if (!$contrib) jsonResponse(['success' => false, 'message' => 'Contribuição não encontrada.'], 404);

        $db->query("DELETE FROM contributions WHERE id = ?", [$id]);
        logActivity('contribution_deleted', 'contribution', $id,
            "Contribuição de {$contrib['full_name']} (". formatMoney($contrib['amount']) .") eliminada.");
        jsonResponse(['success' => true, 'message' => 'Contribuição eliminada com sucesso.']);
        break;

    // ── PENDING ───────────────────────────────────────────
    case 'pending':
        $month = $_GET['month'] ?? date('Y-m');
        $pending = $db->fetchAll(
            "SELECT m.id, m.full_name, m.phone
             FROM members m
             JOIN member_cycles mc ON m.id = mc.member_id AND mc.cycle_id = ? AND mc.status = 'active'
             WHERE m.id NOT IN (SELECT member_id FROM contributions WHERE cycle_id = ? AND reference_month = ?)
             ORDER BY m.full_name",
            [$cycleId, $cycleId, $month . '-01']
        );
        jsonResponse(['success' => true, 'data' => $pending]);
        break;

    // ── SUMMARY ───────────────────────────────────────────
    case 'summary':
        $summary = $db->fetchAll(
            "SELECT DATE_FORMAT(reference_month, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    SUM(late_fee) as total_late_fees,
                    SUM(is_late) as late_count
             FROM contributions WHERE cycle_id = ?
             GROUP BY month ORDER BY month",
            [$cycleId]
        );
        jsonResponse(['success' => true, 'data' => $summary]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
