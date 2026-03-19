<?php
/**
 * ASCA Selecção - Dashboard API
 * Returns JSON data for KPIs and charts
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db = Database::getInstance();
$cycle = getActiveCycle();

if (!$cycle) {
    jsonResponse(['success' => false, 'message' => 'Nenhum ciclo activo encontrado.'], 404);
}

$cycleId = $cycle['id'];

// ── KPIs ────────────────────────────────
$totalMembers = $db->fetch(
    "SELECT COUNT(*) as total FROM member_cycles WHERE cycle_id = ? AND status = 'active'",
    [$cycleId]
)['total'];

$totalContribsOnly = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE cycle_id = ?",
    [$cycleId]
)['total'];

$totalLateFees = $db->fetch(
    "SELECT COALESCE(SUM(late_fee), 0) as total FROM contributions WHERE cycle_id = ? AND is_late = 1",
    [$cycleId]
)['total'];

$totalJoiasPaid = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM joias WHERE cycle_id = ? AND status = 'paid'",
    [$cycleId]
)['total'];

$totalInterest = $db->fetch(
    "SELECT COALESCE(SUM(interest_amount), 0) as total FROM loan_interest WHERE cycle_id = ? AND status = 'paid'",
    [$cycleId]
)['total'];

$totalRepayments = $db->fetch(
    "SELECT COALESCE(SUM(lr.amount), 0) as total FROM loan_repayments lr JOIN loans l ON lr.loan_id = l.id WHERE l.cycle_id = ?",
    [$cycleId]
)['total'];

$totalLoaned = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM loans WHERE cycle_id = ?",
    [$cycleId]
)['total'];

// Fundo Total Acumulado = Património (Contribuições + Multas + Jóias + Juros Pagos)
$totalFund = (float)$totalContribsOnly + (float)$totalLateFees + (float)$totalJoiasPaid + (float)$totalInterest;

// Dívida Activa (Saldo na Rua) = Soma apenas dos empréstimos em aberto (Activos ou em Atraso)
// Isto evita duplicação em casos de renovação (rollover)
$activeDebtResult = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM loans WHERE cycle_id = ? AND status IN ('active', 'overdue')",
    [$cycleId]
);
$activeDebt = (float)$activeDebtResult['total'];

// Reembolsos totais (apenas para fins informativos ou calculos de juros se necessário)
// $totalRepayments já foi calculado acima.

// Capital Disponível = Liquidez em caixa (Fundo Total - Dívida Activa)
$capitalAvailable = $totalFund - $activeDebt;

// Empréstimos activos para contagem no card
$activeLoans = $db->fetch(
    "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM loans WHERE cycle_id = ? AND status = 'active'",
    [$cycleId]
);

$overdueLoans = $db->fetch(
    "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM loans WHERE cycle_id = ? AND status = 'overdue'",
    [$cycleId]
);

$pendingJoias = $db->fetch(
    "SELECT COUNT(*) as total FROM joias WHERE cycle_id = ? AND status = 'pending'",
    [$cycleId]
)['total'];

// Recovery rate
$totalLoansPaid = $db->fetch(
    "SELECT COUNT(*) as total FROM loans WHERE cycle_id = ? AND status = 'paid'",
    [$cycleId]
)['total'];
$totalLoansAll = $db->fetch(
    "SELECT COUNT(*) as total FROM loans WHERE cycle_id = ?",
    [$cycleId]
)['total'];
$recoveryRate = $totalLoansAll > 0 ? round(($totalLoansPaid / $totalLoansAll) * 100, 1) : 0;

// Members below min movement (50,000)
$membersLowMovement = $db->fetchAll(
    "SELECT m.id, m.full_name, COALESCE(SUM(l.amount), 0) as total_movement
     FROM members m
     JOIN member_cycles mc ON m.id = mc.member_id AND mc.cycle_id = ?
     LEFT JOIN loans l ON m.id = l.member_id AND l.cycle_id = ?
     WHERE mc.status = 'active'
     GROUP BY m.id, m.full_name
     HAVING total_movement < ?",
    [$cycleId, $cycleId, $cycle['min_loan_movement']]
);

// ── Chart: Monthly contributions vs loans ──
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(reference_month, '%Y-%m') as month,
            COALESCE(SUM(amount), 0) as total
     FROM contributions WHERE cycle_id = ?
     GROUP BY month ORDER BY month",
    [$cycleId]
);

$monthlyLoans = $db->fetchAll(
    "SELECT DATE_FORMAT(disbursement_date, '%Y-%m') as month,
            COALESCE(SUM(amount), 0) as total
     FROM loans WHERE cycle_id = ?
     GROUP BY month ORDER BY month",
    [$cycleId]
);

// ── Auto-update overdue status ──
$db->query(
    "UPDATE loans SET status = 'overdue' 
     WHERE cycle_id = ? AND status = 'active' AND due_date < CURDATE()",
    [$cycleId]
);

// ── Comprehensive Upcoming Due (Loans + Contributions) ──
$upcomingDue = [];
$today = new DateTime();
$todayStr = $today->format('Y-m-d');

// 1. OVERDUE loans (past due but not paid) — highest priority
$overdueLoansData = $db->fetchAll(
    "SELECT l.id, l.member_id, l.amount, l.due_date, m.full_name, m.phone, 'loan' as type, 'overdue' as urgency
     FROM loans l 
     JOIN members m ON l.member_id = m.id
     WHERE l.cycle_id = ? AND l.status = 'overdue'
     ORDER BY l.due_date ASC",
    [$cycleId]
);
foreach ($overdueLoansData as $l) $upcomingDue[] = $l;

// 2. Active loans (due in the next 30 days) — wider window for usefulness
$upcomingLoans = $db->fetchAll(
    "SELECT l.id, l.member_id, l.amount, l.due_date, m.full_name, m.phone, 'loan' as type, 'upcoming' as urgency
     FROM loans l 
     JOIN members m ON l.member_id = m.id
     WHERE l.cycle_id = ? AND l.status = 'active' 
       AND l.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY l.due_date ASC",
    [$cycleId]
);
foreach ($upcomingLoans as $l) $upcomingDue[] = $l;

// 3. Monthly Contributions — members who haven't paid for the current reference month
$deadlineDay = (int)($cycle['monthly_deadline_day'] ?? 10);
$padDay = str_pad($deadlineDay, 2, '0', STR_PAD_LEFT);

// Current month's reference: the contribution for "this month" (reference_month = first day of current month)
$currentRefMonth = $today->format('Y-m-01');
$deadlineDateStr = $today->format("Y-m-{$padDay}");

// Also check last month if we're before the deadline day
$lastRefMonth = (new DateTime())->modify('first day of last month')->format('Y-m-01');
$lastDeadlineDateStr = $today->format("Y-m-{$padDay}"); // deadline is always in current month

$pendingContribs = $db->fetchAll(
    "SELECT m.id, m.id as member_id, m.phone, ? as amount, ? as due_date, m.full_name, 'contribution' as type,
            CASE WHEN CURDATE() > ? THEN 'overdue' ELSE 'upcoming' END as urgency
     FROM members m
     JOIN member_cycles mc ON m.id = mc.member_id
     WHERE mc.cycle_id = ? AND mc.status = 'active'
       AND m.id NOT IN (
           SELECT member_id FROM contributions 
           WHERE cycle_id = ? AND reference_month = ?
       )",
    [$cycle['min_monthly'], $deadlineDateStr, $deadlineDateStr, $cycleId, $cycleId, $currentRefMonth]
);
foreach ($pendingContribs as $pc) $upcomingDue[] = $pc;

// Sort: overdue first, then by due_date
usort($upcomingDue, function($a, $b) {
    if ($a['urgency'] === 'overdue' && $b['urgency'] !== 'overdue') return -1;
    if ($a['urgency'] !== 'overdue' && $b['urgency'] === 'overdue') return 1;
    return strcmp($a['due_date'], $b['due_date']);
});

// Limit to 12
$upcomingDue = array_slice($upcomingDue, 0, 12);

// Recent activity
$recentActivity = $db->fetchAll(
    "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 8"
);

jsonResponse([
    'success' => true,
    'kpis' => [
        'total_members'       => (int)$totalMembers,
        'total_fund'          => (float)$totalFund,
        'total_loaned'        => (float)$activeDebt,
        'total_interest'      => (float)$totalInterest,
        'total_late_fees'     => (float)$totalLateFees,
        'active_loans_count'  => (int)$activeLoans['count'],
        'active_loans_total'  => (float)$activeLoans['total'],
        'overdue_loans_count' => (int)$overdueLoans['count'],
        'overdue_loans_total' => (float)$overdueLoans['total'],
        'pending_joias'       => (int)$pendingJoias,
        'capital_available'   => (float)$capitalAvailable,
        'total_gross_loaned'  => (float)$totalLoaned, // Aditional field for internal tracking
        'recovery_rate'       => $recoveryRate,
        'members_low_movement'=> count($membersLowMovement),
    ],
    'charts' => [
        'monthly_contributions' => $monthlyData,
        'monthly_loans'         => $monthlyLoans,
    ],
    'upcoming_due' => $upcomingDue,
    'recent_activity' => $recentActivity,
    'members_low_movement_list' => $membersLowMovement,
]);
