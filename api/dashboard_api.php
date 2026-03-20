<?php
/**
 * ASCA Selecção - Dashboard API
 * Returns JSON data for KPIs, charts, upcoming obligations and recent activity.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db    = Database::getInstance();
$cycle = getActiveCycle();

if (!$cycle) {
    jsonResponse(['success' => false, 'message' => 'Nenhum ciclo activo encontrado.'], 404);
}

$cycleId = $cycle['id'];

// ── Auto-update overdue status ────────────────────────────────────────────────
$db->query(
    "UPDATE loans SET status = 'overdue' WHERE cycle_id = ? AND status = 'active' AND due_date < CURDATE()",
    [$cycleId]
);

// ── KPIs — Inflows ────────────────────────────────────────────────────────────

$totalContribsOnly = (float)$db->fetch(
    "SELECT COALESCE(SUM(amount),0) as t FROM contributions WHERE cycle_id = ?",
    [$cycleId]
)['t'];

$totalLateFees = (float)$db->fetch(
    "SELECT COALESCE(SUM(late_fee),0) as t FROM contributions WHERE cycle_id = ? AND is_late = 1",
    [$cycleId]
)['t'];

$totalJoiasPaid = (float)$db->fetch(
    "SELECT COALESCE(SUM(amount),0) as t FROM joias WHERE cycle_id = ? AND status = 'paid'",
    [$cycleId]
)['t'];

$totalInterest = (float)$db->fetch(
    "SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'paid'",
    [$cycleId]
)['t'];

// Pending interest (not yet collected)
$pendingInterest = (float)$db->fetch(
    "SELECT COALESCE(SUM(interest_amount),0) as t FROM loan_interest WHERE cycle_id = ? AND status = 'pending'",
    [$cycleId]
)['t'];

// ── Total Fund (patrimony) ────────────────────────────────────────────────────
// All inflows received: contributions + late fees + joias + paid interest
$totalFund = $totalContribsOnly + $totalLateFees + $totalJoiasPaid + $totalInterest;

// ── Active Debt (outstanding balance) ────────────────────────────────────────
// Correct formula: sum of (loan_amount - repayments_already_made) for active/overdue loans.
// Using total loan principal would OVER-state the debt when partial repayments exist.
$activeDebt = (float)$db->fetch(
    "SELECT COALESCE(SUM(
         l.amount - COALESCE((SELECT SUM(lr.amount) FROM loan_repayments lr WHERE lr.loan_id = l.id), 0)
     ), 0) as t
     FROM loans l
     WHERE l.cycle_id = ? AND l.status IN ('active','overdue')",
    [$cycleId]
)['t'];

// Capital Available = cash on hand
$capitalAvailable = $totalFund - $activeDebt;

// ── Loan counts ───────────────────────────────────────────────────────────────
$activeLoans = $db->fetch(
    "SELECT COUNT(*) as count, COALESCE(SUM(amount),0) as total
     FROM loans WHERE cycle_id = ? AND status = 'active'",
    [$cycleId]
);

$overdueLoans = $db->fetch(
    "SELECT COUNT(*) as count, COALESCE(SUM(amount),0) as total
     FROM loans WHERE cycle_id = ? AND status = 'overdue'",
    [$cycleId]
);

// ── Recovery Rate (by amount) ─────────────────────────────────────────────────
// Total cash repayments received / total principal ever disbursed.
// More meaningful than count-based rate: shows what % of credit was actually recovered.
$totalRepayments = (float)$db->fetch(
    "SELECT COALESCE(SUM(lr.amount),0) as t
     FROM loan_repayments lr JOIN loans l ON lr.loan_id = l.id WHERE l.cycle_id = ?",
    [$cycleId]
)['t'];

$totalLoaned = (float)$db->fetch(
    "SELECT COALESCE(SUM(amount),0) as t FROM loans WHERE cycle_id = ?",
    [$cycleId]
)['t'];

$recoveryRate = $totalLoaned > 0 ? round(($totalRepayments / $totalLoaned) * 100, 1) : 0.0;

// ── Member stats ──────────────────────────────────────────────────────────────
$totalMembers = (int)$db->fetch(
    "SELECT COUNT(*) as t FROM member_cycles WHERE cycle_id = ? AND status = 'active'",
    [$cycleId]
)['t'];

$pendingJoias = (int)$db->fetch(
    "SELECT COUNT(*) as t FROM joias WHERE cycle_id = ? AND status = 'pending'",
    [$cycleId]
)['t'];

// Monthly compliance: how many members paid the current reference month
$currentRefMonth = date('Y-m-01');
$paidThisMonth = (int)$db->fetch(
    "SELECT COUNT(DISTINCT member_id) as t FROM contributions WHERE cycle_id = ? AND reference_month = ?",
    [$cycleId, $currentRefMonth]
)['t'];

// Members below minimum loan movement threshold
$membersLowMovement = $db->fetchAll(
    "SELECT m.id, m.full_name, COALESCE(SUM(l.amount),0) as total_movement
     FROM members m
     JOIN member_cycles mc ON m.id = mc.member_id AND mc.cycle_id = ?
     LEFT JOIN loans l ON m.id = l.member_id AND l.cycle_id = ?
     WHERE mc.status = 'active'
     GROUP BY m.id, m.full_name
     HAVING total_movement < ?",
    [$cycleId, $cycleId, (float)$cycle['min_loan_movement']]
);

// ── Chart: Monthly contributions vs loans disbursed ───────────────────────────
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(reference_month, '%Y-%m') as month, COALESCE(SUM(amount),0) as total
     FROM contributions WHERE cycle_id = ?
     GROUP BY month ORDER BY month",
    [$cycleId]
);

$monthlyLoans = $db->fetchAll(
    "SELECT DATE_FORMAT(disbursement_date, '%Y-%m') as month, COALESCE(SUM(amount),0) as total
     FROM loans WHERE cycle_id = ?
     GROUP BY month ORDER BY month",
    [$cycleId]
);

// ── Upcoming Obligations ──────────────────────────────────────────────────────
$upcomingDue = [];
$today       = new DateTime();

// 1. Overdue loans (highest priority)
$overdueLoansData = $db->fetchAll(
    "SELECT l.id, l.member_id, l.amount, l.due_date, m.full_name, m.phone,
            'loan' as type, 'overdue' as urgency
     FROM loans l JOIN members m ON l.member_id = m.id
     WHERE l.cycle_id = ? AND l.status = 'overdue'
     ORDER BY l.due_date ASC",
    [$cycleId]
);
foreach ($overdueLoansData as $l) $upcomingDue[] = $l;

// 2. Active loans due in next 30 days
$upcomingLoans = $db->fetchAll(
    "SELECT l.id, l.member_id, l.amount, l.due_date, m.full_name, m.phone,
            'loan' as type, 'upcoming' as urgency
     FROM loans l JOIN members m ON l.member_id = m.id
     WHERE l.cycle_id = ? AND l.status = 'active'
       AND l.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY l.due_date ASC",
    [$cycleId]
);
foreach ($upcomingLoans as $l) $upcomingDue[] = $l;

// 3. Members who haven't paid the current month's contribution
$deadlineDay = (int)($cycle['monthly_deadline_day'] ?? 10);
$padDay      = str_pad($deadlineDay, 2, '0', STR_PAD_LEFT);
$deadlineDateStr = $today->format("Y-m-{$padDay}");

$pendingContribs = $db->fetchAll(
    "SELECT m.id as member_id, m.phone, ? as amount, ? as due_date, m.full_name,
            'contribution' as type,
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

// Sort: overdue first, then by date
usort($upcomingDue, function ($a, $b) {
    if ($a['urgency'] === 'overdue' && $b['urgency'] !== 'overdue') return -1;
    if ($a['urgency'] !== 'overdue' && $b['urgency'] === 'overdue') return 1;
    return strcmp($a['due_date'], $b['due_date']);
});
$upcomingDue = array_slice($upcomingDue, 0, 15);

// ── Recent Activity ───────────────────────────────────────────────────────────
$recentActivity = $db->fetchAll(
    "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10"
);

// ── Cycle progress ────────────────────────────────────────────────────────────
$cycleProgress = null;
if (!empty($cycle['start_date']) && !empty($cycle['end_date'])) {
    $start    = new DateTime($cycle['start_date']);
    $end      = new DateTime($cycle['end_date']);
    $now      = new DateTime();
    $totalDays = (int)$start->diff($end)->days;
    $elapsed   = (int)$start->diff($now)->days;
    $pct       = $totalDays > 0 ? min(100, round(($elapsed / $totalDays) * 100)) : 0;
    $cycleProgress = [
        'start_date'   => $cycle['start_date'],
        'end_date'     => $cycle['end_date'],
        'total_days'   => $totalDays,
        'elapsed_days' => max(0, $elapsed),
        'pct'          => $pct,
    ];
}

// ── Response ──────────────────────────────────────────────────────────────────
jsonResponse([
    'success' => true,
    'kpis' => [
        'total_members'         => $totalMembers,
        'total_fund'            => $totalFund,
        'total_loaned'          => $activeDebt,          // outstanding balance (not principal)
        'total_interest'        => $totalInterest,
        'pending_interest'      => $pendingInterest,
        'total_late_fees'       => $totalLateFees,
        'active_loans_count'    => (int)$activeLoans['count'],
        'active_loans_total'    => (float)$activeLoans['total'],
        'overdue_loans_count'   => (int)$overdueLoans['count'],
        'overdue_loans_total'   => (float)$overdueLoans['total'],
        'pending_joias'         => $pendingJoias,
        'capital_available'     => $capitalAvailable,
        'recovery_rate'         => $recoveryRate,
        'members_low_movement'  => count($membersLowMovement),
        'paid_this_month'       => $paidThisMonth,
        'total_members_active'  => $totalMembers,
    ],
    'charts' => [
        'monthly_contributions' => $monthlyData,
        'monthly_loans'         => $monthlyLoans,
    ],
    'upcoming_due'              => $upcomingDue,
    'recent_activity'           => $recentActivity,
    'members_low_movement_list' => $membersLowMovement,
    'cycle_progress'            => $cycleProgress,
]);
