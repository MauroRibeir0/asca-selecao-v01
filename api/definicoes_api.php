<?php
/**
 * ASCA Selecção - Settings API
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN);

$db = Database::getInstance();
$cycle = getActiveCycle();

if (!$cycle) {
    jsonResponse(['success' => false, 'message' => 'Nenhum ciclo activo.'], 404);
}

// Save settings
$name               = sanitize($_POST['name'] ?? '');
$startDate          = $_POST['start_date'] ?? '';
$endDate            = $_POST['end_date'] ?? '';
$joiaAmount         = (float)($_POST['joia_amount'] ?? 0);
$joiaDeadline       = $_POST['joia_deadline'] ?? '';
$minMonthly         = (float)($_POST['min_monthly'] ?? 0);
$maxMonthly         = (float)($_POST['max_monthly'] ?? 0);
$monthlyDeadlineDay = (int)($_POST['monthly_deadline_day'] ?? 10);
$lateFeePct         = (float)($_POST['late_fee_pct'] ?? 0);
$loanInterestPct    = (float)($_POST['loan_interest_pct'] ?? 0);
$loanRepaymentDays  = (int)($_POST['loan_repayment_days'] ?? 30);
$loanToleranceMargin = (float)($_POST['loan_tolerance_margin'] ?? 0);
$minLoanMovement    = (float)($_POST['min_loan_movement'] ?? 0);
$allowMultipleLoans = (int)($_POST['allow_multiple_loans'] ?? 0);
$fixedInterest      = (float)($_POST['fixed_interest_entitlement'] ?? 0);

// Validation
if (empty($name)) {
    jsonResponse(['success' => false, 'message' => 'O nome do ciclo é obrigatório.']);
}
if ($minMonthly > $maxMonthly && $maxMonthly > 0) {
    jsonResponse(['success' => false, 'message' => 'A contribuição mínima não pode ser superior à máxima.']);
}
if ($monthlyDeadlineDay < 1 || $monthlyDeadlineDay > 28) {
    jsonResponse(['success' => false, 'message' => 'O dia limite deve ser entre 1 e 28.']);
}

try {
    $db->query(
        "UPDATE cycles SET 
            name = ?, start_date = ?, end_date = ?, 
            joia_amount = ?, joia_deadline = ?,
            min_monthly = ?, max_monthly = ?, monthly_deadline_day = ?,
            late_fee_pct = ?,
            loan_interest_pct = ?, loan_repayment_days = ?,
            loan_tolerance_margin = ?, min_loan_movement = ?, allow_multiple_loans = ?,
            fixed_interest_entitlement = ?
         WHERE id = ?",
        [
            $name, $startDate, $endDate,
            $joiaAmount, $joiaDeadline,
            $minMonthly, $maxMonthly, $monthlyDeadlineDay,
            $lateFeePct,
            $loanInterestPct, $loanRepaymentDays,
            $loanToleranceMargin, $minLoanMovement, $allowMultipleLoans,
            $fixedInterest,
            $cycle['id']
        ]
    );

    logActivity('settings_updated', 'cycle', $cycle['id'], 'Definições do ciclo actualizadas');
    jsonResponse(['success' => true, 'message' => 'Definições guardadas com sucesso.']);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao guardar: ' . $e->getMessage()]);
}
