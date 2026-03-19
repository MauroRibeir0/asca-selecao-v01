<?php
/**
 * ASCA Selecção - Loans API
 * 
 * Repayment rules:
 * - Due date = disbursement_date + 30 days
 * - If paid_date > due_date: each extra 30-day period increases the interest rate arithmetically (15%→30%→45%…)
 * - "Interest only" (paid before due): capital stays, auto-creates a NEW loan starting on due_date
 * - "Partial" (>= interest amount): interest first, remainder reduces capital, leftover capital
 *    auto-creates a NEW loan starting on original due_date
 * - Partial < interest: BLOCKED
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
 * Calculate scaled interest rate and amount based on how many 30-day periods
 * have passed since the original due date.
 *
 * Returns ['rate' => float, 'amount' => float, 'overdue_periods' => int]
 */
function calcScaledInterest(float $principal, string $dueDate, string $paidDate, float $baseRate): array {
    $due  = new DateTime($dueDate);
    $paid = new DateTime($paidDate);

    if ($paid <= $due) {
        // Not overdue: standard interest
        $amount = round($principal * ($baseRate / 100), 2);
        return ['rate' => $baseRate, 'amount' => $amount, 'overdue_periods' => 0, 'effective_due' => $dueDate];
    }

    // Count 30-day periods past the original due date
    $diff    = $due->diff($paid)->days; // total days overdue
    $periods = (int)ceil($diff / 30);   // each period increases rate arithmetically (15->30->45...)

    $rate = $baseRate * (1 + $periods);

    // Effective due date advances 30 days per period
    $effectiveDue = clone $due;
    $daysToMove = $periods * 30;
    $effectiveDue->modify("+{$daysToMove} days");

    $amount = round($principal * ($rate / 100), 2);
    return ['rate' => $rate, 'amount' => $amount, 'overdue_periods' => $periods, 'effective_due' => $effectiveDue->format('Y-m-d')];
}

switch ($action) {

    // ── LIST LOANS ─────────────────────────────────────────
    case 'list':
        $status = $_GET['status'] ?? '';
        $where  = "l.cycle_id = ?";
        $params = [$cycleId];
        if ($status) {
            $where .= " AND l.status = ?";
            $params[] = $status;
        }

        $loans = $db->fetchAll(
            "SELECT l.*, m.full_name,
                    (SELECT COALESCE(SUM(lr.amount),0) FROM loan_repayments lr WHERE lr.loan_id = l.id) as total_repaid,
                    (SELECT COALESCE(SUM(li.interest_amount),0) FROM loan_interest li WHERE li.loan_id = l.id AND li.status = 'paid') as total_interest_paid,
                    (SELECT COALESCE(SUM(li2.interest_amount),0) FROM loan_interest li2 WHERE li2.loan_id = l.id AND li2.status = 'pending') as total_interest_pending
             FROM loans l
             JOIN members m ON l.member_id = m.id
             WHERE {$where} AND m.status = 'active'
             ORDER BY l.disbursement_date DESC",
            $params
        );

        // Auto-update overdue status
        foreach ($loans as &$loan) {
            if ($loan['status'] === 'active' && $loan['due_date'] < date('Y-m-d')) {
                $db->query("UPDATE loans SET status = 'overdue' WHERE id = ?", [$loan['id']]);
                $loan['status'] = 'overdue';
            }
        }
        unset($loan);

        jsonResponse(['success' => true, 'data' => $loans]);
        break;

    // ── GET SINGLE LOAN ────────────────────────────────────
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $loan = $db->fetch(
            "SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.id = ? AND l.cycle_id = ?",
            [$id, $cycleId]
        );
        if (!$loan) jsonResponse(['success' => false, 'message' => 'Empréstimo não encontrado.'], 404);
        jsonResponse(['success' => true, 'data' => $loan]);
        break;

    // ── CREATE LOAN ────────────────────────────────────────
    case 'create':
        $memberId = (int)($_POST['member_id'] ?? 0);
        $amount   = (float)($_POST['amount'] ?? 0);
        $disbDate = $_POST['disbursement_date'] ?? date('Y-m-d');
        $notes    = sanitize($_POST['notes'] ?? '');

        if (!hasJoiaPaid($memberId, $cycleId)) {
            jsonResponse(['success' => false, 'message' => 'Membro não pode pedir empréstimo sem jóia paga.'], 422);
        }

        $minAmount = (float)($cycle['min_loan_amount'] ?? DEFAULT_MIN_LOAN_AMOUNT);
        if ($amount < $minAmount) {
            jsonResponse(['success' => false, 'message' => "O valor mínimo para empréstimo é de " . formatMoney($minAmount) . "."], 422);
        }

        // ── LOAN CAPACITY CHECK ───────────────────────────────
        $adminOverride = !empty($_POST['admin_override']) && isAdmin();

        // Member savings = sum of contributions for this cycle
        $memberSavingsRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE member_id = ? AND cycle_id = ?",
            [$memberId, $cycleId]
        );
        $memberSavings = (float)$memberSavingsRow['total'];

        // Group total savings
        $groupSavingsRow = $db->fetch(
            "SELECT COALESCE(SUM(c.amount), 0) as total FROM contributions c
             JOIN member_cycles mc ON mc.member_id = c.member_id AND mc.cycle_id = c.cycle_id
             WHERE c.cycle_id = ?",
            [$cycleId]
        );
        $groupSavings = (float)$groupSavingsRow['total'];

        // Total interest paid in the cycle
        $totalInterestRow = $db->fetch(
            "SELECT COALESCE(SUM(interest_amount), 0) as total FROM loan_interest WHERE cycle_id = ? AND status = 'paid'",
            [$cycleId]
        );
        $totalInterestPaid = (float)$totalInterestRow['total'];

        // Proportional interest share
        $interestShare = ($groupSavings > 0) ? ($memberSavings / $groupSavings) * $totalInterestPaid : 0.0;

        // Tolerance margin from cycle settings
        $tolerance = (float)($cycle['loan_tolerance_margin'] ?? 0);

        // Active debt = only active + overdue loans (not paid/rolled originals)
        $activeDebtRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM loans
             WHERE member_id = ? AND cycle_id = ? AND status IN ('active', 'overdue')",
            [$memberId, $cycleId]
        );
        $memberActiveDebt = (float)$activeDebtRow['total'];

        // Capacity = savings + interest_share + tolerance - active_debt
        $capacity = $memberSavings + $interestShare + $tolerance - $memberActiveDebt;

        if ($amount > $capacity + 0.01 && !$adminOverride) {
            $memberRow = $db->fetch("SELECT full_name FROM members WHERE id = ?", [$memberId]);
            jsonResponse([
                'success'   => false,
                'message'   => 'Valor solicitado excede a capacidade de endividamento do membro.',
                'capacity_breakdown' => [
                    'member_savings'     => round($memberSavings, 2),
                    'interest_share'     => round($interestShare, 2),
                    'tolerance'          => round($tolerance, 2),
                    'active_debt'        => round($memberActiveDebt, 2),
                    'capacity'           => round($capacity, 2),
                    'requested'          => round($amount, 2),
                    'shortfall'          => round($amount - $capacity, 2),
                ]
            ], 422);
        }

        if ($adminOverride && $amount > $capacity + 0.01) {
            $memberRow = $db->fetch("SELECT full_name FROM members WHERE id = ?", [$memberId]);
            logActivity('loan_capacity_override', 'loan', null,
                "Override de capacidade: {$memberRow['full_name']} solicitou " . formatMoney($amount) .
                " (capacidade: " . formatMoney($capacity) . ")");
        }
        // ─────────────────────────────────────────────────────

        $dueDate = calculateLoanDueDate($disbDate, $cycle['loan_repayment_days']);

        $db->query(
            "INSERT INTO loans (member_id, cycle_id, amount, disbursement_date, due_date, notes) VALUES (?, ?, ?, ?, ?, ?)",
            [$memberId, $cycleId, $amount, $disbDate, $dueDate, $notes]
        );
        $loanId = (int)$db->lastInsertId();

        // Create first interest charge
        $interestAmount = round($amount * ($cycle['loan_interest_pct'] / 100), 2);
        $db->query(
            "INSERT INTO loan_interest (loan_id, member_id, cycle_id, reference_month, interest_rate, interest_amount)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$loanId, $memberId, $cycleId, date('Y-m-01', strtotime($disbDate)), $cycle['loan_interest_pct'], $interestAmount]
        );

        $memberName = $db->fetch("SELECT full_name FROM members WHERE id = ?", [$memberId])['full_name'];
        logActivity('loan_created', 'loan', $loanId, "Empréstimo de " . formatMoney($amount) . " para {$memberName}");

        jsonResponse([
            'success' => true,
            'message' => "Empréstimo de " . formatMoney($amount) . " desembolsado. Vencimento: " . formatDate($dueDate) . ". Juros: " . formatMoney($interestAmount)
        ]);
        break;

    // ── UPDATE LOAN ────────────────────────────────────────
    case 'update':
        requireRole(ROLE_ADMIN);
        $id       = (int)($_POST['id'] ?? 0);
        $amount   = (float)($_POST['amount'] ?? 0);
        $disbDate = $_POST['disbursement_date'] ?? '';
        $notes    = sanitize($_POST['notes'] ?? '');

        $loan = $db->fetch("SELECT * FROM loans WHERE id = ? AND cycle_id = ?", [$id, $cycleId]);
        if (!$loan) jsonResponse(['success' => false, 'message' => 'Empréstimo não encontrado.'], 404);

        // Prevent editing paid loans
        if ($loan['status'] === 'paid') {
            jsonResponse(['success' => false, 'message' => 'Não é possível editar um empréstimo já liquidado.'], 422);
        }

        $dueDate = calculateLoanDueDate($disbDate, $cycle['loan_repayment_days']);

        $db->query(
            "UPDATE loans SET amount=?, disbursement_date=?, due_date=?, notes=? WHERE id=? AND cycle_id=?",
            [$amount, $disbDate, $dueDate, $notes, $id, $cycleId]
        );

        // Recalculate first interest if amount changed
        $interestAmount = round($amount * ($cycle['loan_interest_pct'] / 100), 2);
        $db->query(
            "UPDATE loan_interest SET interest_amount=? WHERE loan_id=? ORDER BY reference_month ASC LIMIT 1",
            [$interestAmount, $id]
        );

        logActivity('loan_updated', 'loan', $id, "Empréstimo actualizado: " . formatMoney($amount));
        jsonResponse(['success' => true, 'message' => 'Empréstimo actualizado com sucesso.']);
        break;

    // ── DELETE LOAN ────────────────────────────────────────
    case 'delete':
        requireRole(ROLE_ADMIN);
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $loan = $db->fetch(
            "SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.id = ? AND l.cycle_id = ?",
            [$id, $cycleId]
        );
        if (!$loan) jsonResponse(['success' => false, 'message' => 'Empréstimo não encontrado.'], 404);

        // Check if there are repayments
        $repayCount = (int)$db->fetch("SELECT COUNT(*) as cnt FROM loan_repayments WHERE loan_id = ?", [$id])['cnt'];
        if ($repayCount > 0) {
            jsonResponse(['success' => false, 'message' => 'Não é possível eliminar um empréstimo com reembolsos registados.'], 422);
        }

        $db->query("DELETE FROM loan_interest WHERE loan_id = ?", [$id]);
        $db->query("DELETE FROM loans WHERE id = ?", [$id]);
        logActivity('loan_deleted', 'loan', $id, "Empréstimo de {$loan['full_name']} (" . formatMoney($loan['amount']) . ") eliminado.");
        jsonResponse(['success' => true, 'message' => 'Empréstimo eliminado com sucesso.']);
        break;

    // ── CALCULATE INTEREST PREVIEW ─────────────────────────
    case 'calc_interest':
        $loanId   = (int)($_GET['loan_id'] ?? 0);
        $paidDate = $_GET['paid_date'] ?? date('Y-m-d');

        $loan = $db->fetch("SELECT * FROM loans WHERE id = ? AND cycle_id = ?", [$loanId, $cycleId]);
        if (!$loan) jsonResponse(['success' => false, 'message' => 'Empréstimo não encontrado.'], 404);

        $totalRepaid = (float)$db->fetch(
            "SELECT COALESCE(SUM(amount),0) as total FROM loan_repayments WHERE loan_id = ?", [$loanId]
        )['total'];
        $remainingPrincipal = (float)$loan['amount'] - $totalRepaid;

        // Get pending interests from DB (already stored)
        $pendingInterests = $db->fetchAll(
            "SELECT * FROM loan_interest WHERE loan_id = ? AND status = 'pending' ORDER BY reference_month ASC",
            [$loanId]
        );
        $storedPendingInterest = array_reduce($pendingInterests, fn($s, $i) => $s + (float)$i['interest_amount'], 0);

        // Calculate scaled interest based on payment date
        $scaled = calcScaledInterest($remainingPrincipal, $loan['due_date'], $paidDate, (float)$cycle['loan_interest_pct']);

        // The effective interest to show is the max of stored pending or scaled
        $effectiveInterest = max($storedPendingInterest, $scaled['amount']);

        jsonResponse([
            'success'          => true,
            'remaining_principal' => $remainingPrincipal,
            'stored_pending_interest' => $storedPendingInterest,
            'scaled_interest_amount'  => $scaled['amount'],
            'scaled_interest_rate'    => $scaled['rate'],
            'effective_interest'      => $effectiveInterest,
            'overdue_periods'         => $scaled['overdue_periods'],
            'effective_due'           => $scaled['effective_due'],
            'is_overdue'              => $scaled['overdue_periods'] > 0,
        ]);
        break;

    // ── REPAY LOAN ─────────────────────────────────────────
    case 'repay':
        $loanId      = (int)($_POST['loan_id'] ?? 0);
        $amount      = (float)($_POST['amount'] ?? 0);
        $paidDate    = $_POST['paid_date'] ?? date('Y-m-d');
        $method      = $_POST['payment_method'] ?? 'cash';
        $receipt     = sanitize($_POST['receipt_ref'] ?? '');
        $paymentType = $_POST['payment_type'] ?? 'total'; // total | interest_only | partial

        $loan = $db->fetch("SELECT * FROM loans WHERE id = ?", [$loanId]);
        if (!$loan) jsonResponse(['success' => false, 'message' => 'Empréstimo não encontrado.'], 404);
        if ($loan['status'] === 'paid') jsonResponse(['success' => false, 'message' => 'Este empréstimo já foi liquidado.'], 422);

        $totalRepaid = (float)$db->fetch(
            "SELECT COALESCE(SUM(amount),0) as total FROM loan_repayments WHERE loan_id = ?", [$loanId]
        )['total'];

        $pendingInterests = $db->fetchAll(
            "SELECT * FROM loan_interest WHERE loan_id = ? AND status = 'pending' ORDER BY reference_month ASC",
            [$loanId]
        );
        $storedPendingInterest = array_reduce($pendingInterests, fn($s, $i) => $s + (float)$i['interest_amount'], 0);

        $remainingPrincipal = (float)$loan['amount'] - $totalRepaid;

        // Calculate scaled interest based on actual payment date
        $scaled = calcScaledInterest($remainingPrincipal, $loan['due_date'], $paidDate, (float)$cycle['loan_interest_pct']);
        $effectiveInterest = max($storedPendingInterest, $scaled['amount']);

        // ── Type: INTEREST ONLY ──────────────────────────────
        if ($paymentType === 'interest_only') {
            // Block if paying after due date (interest_only is only valid before due date)
            if ($paidDate > $loan['due_date']) {
                jsonResponse(['success' => false, 'message' =>
                    'Pagamento apenas dos juros não é permitido após o vencimento. O pagamento fora do prazo tem juros escalados (' . number_format($scaled['rate'], 0) . '%). Use "Pagamento Parcial" ou "Liquidar Total".'], 422);
            }

            if (abs($amount - $effectiveInterest) > 0.01) {
                jsonResponse(['success' => false, 'message' =>
                    'Para "Apenas Juros", o valor deve ser exactamente ' . formatMoney($effectiveInterest) . '.'], 422);
            }

            // Mark existing pending interests as paid
            foreach ($pendingInterests as $interest) {
                $db->query("UPDATE loan_interest SET status = 'paid', paid_date = ? WHERE id = ?", [$paidDate, $interest['id']]);
            }

            // !! Capital automatically becomes a NEW loan starting from the day after current due_date
            $newDisbDate = $loan['due_date']; // new loan starts on the due_date of current loan
            $newDueDate  = calculateLoanDueDate($newDisbDate, $cycle['loan_repayment_days']);
            $newInterest = round($remainingPrincipal * ($cycle['loan_interest_pct'] / 100), 2);

            $db->query(
                "INSERT INTO loans (member_id, cycle_id, amount, disbursement_date, due_date, notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'active')",
                [$loan['member_id'], $cycleId, $remainingPrincipal, $newDisbDate, $newDueDate,
                 "Renovação automática do empréstimo #{$loanId} por pagamento apenas de juros."]
            );
            $newLoanId = (int)$db->lastInsertId();

            $db->query(
                "INSERT INTO loan_interest (loan_id, member_id, cycle_id, reference_month, interest_rate, interest_amount)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$newLoanId, $loan['member_id'], $cycleId, date('Y-m-01', strtotime($newDisbDate)),
                 $cycle['loan_interest_pct'], $newInterest]
            );

            // Close the original loan
            $db->query("UPDATE loans SET status = 'paid' WHERE id = ?", [$loanId]);

            logActivity('loan_repaid', 'loan', $loanId,
                "Pagamento Apenas Juros: " . formatMoney($amount) . ". Capital " . formatMoney($remainingPrincipal) . " → novo empréstimo #{$newLoanId}");

            jsonResponse(['success' => true, 'message' =>
                "Juros de " . formatMoney($amount) . " registados. O capital de " . formatMoney($remainingPrincipal) .
                " foi automaticamente convertido num novo empréstimo (Empréstimo #" . $newLoanId .
                "), com vencimento em " . formatDate($newDueDate) . " e juros de " . formatMoney($newInterest) . "."
            ]);
            break;
        }

        // ── Type: PARTIAL ────────────────────────────────────
        if ($paymentType === 'partial') {
            // Block if amount < effective interest
            if ($amount < $effectiveInterest - 0.01) {
                jsonResponse(['success' => false, 'message' =>
                    'Pagamento parcial não pode ser inferior ao valor dos juros (' . formatMoney($effectiveInterest) . '). Aumente o valor ou seleccione "Apenas Juros".'], 422);
            }

            // Pay interest first (FIFO)
            $remaining = $amount;
            foreach ($pendingInterests as $interest) {
                if ($remaining <= 0) break;
                $iAmt = (float)$interest['interest_amount'];
                if ($remaining >= $iAmt) {
                    $db->query("UPDATE loan_interest SET status = 'paid', paid_date = ? WHERE id = ?", [$paidDate, $interest['id']]);
                    $remaining -= $iAmt;
                }
                // If scaled interest > stored, update the stored record amount
            }

            // If amount covered more interest than stored (due to scaling), record the difference
            if ($scaled['overdue_periods'] > 0 && $effectiveInterest > $storedPendingInterest) {
                $diff = $effectiveInterest - $storedPendingInterest;
                if ($remaining >= $diff) {
                    $remaining -= $diff;
                }
            }

            // The remaining after interest goes to reduce principal
            $capitalRepaid = min($remaining, $remainingPrincipal);

            if ($capitalRepaid > 0) {
                $db->query(
                    "INSERT INTO loan_repayments (loan_id, member_id, amount, paid_date, payment_method, receipt_ref)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$loanId, $loan['member_id'], $capitalRepaid, $paidDate, $method, $receipt]
                );
            }

            $newPrincipal = $remainingPrincipal - $capitalRepaid;

            if ($newPrincipal > 0.01) {
                // Remaining capital → new loan starting on original due_date
                $newDisbDate = $loan['due_date'];
                $newDueDate  = calculateLoanDueDate($newDisbDate, $cycle['loan_repayment_days']);
                $newInterest = round($newPrincipal * ($cycle['loan_interest_pct'] / 100), 2);

                $db->query(
                    "INSERT INTO loans (member_id, cycle_id, amount, disbursement_date, due_date, notes, status)
                     VALUES (?, ?, ?, ?, ?, ?, 'active')",
                    [$loan['member_id'], $cycleId, $newPrincipal, $newDisbDate, $newDueDate,
                     "Saldo remanescente do empréstimo #{$loanId} após pagamento parcial."]
                );
                $newLoanId = (int)$db->lastInsertId();

                $db->query(
                    "INSERT INTO loan_interest (loan_id, member_id, cycle_id, reference_month, interest_rate, interest_amount)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$newLoanId, $loan['member_id'], $cycleId, date('Y-m-01', strtotime($newDisbDate)),
                     $cycle['loan_interest_pct'], $newInterest]
                );

                // Close original loan
                $db->query("UPDATE loans SET status = 'paid' WHERE id = ?", [$loanId]);

                logActivity('loan_repaid', 'loan', $loanId,
                    "Pagamento Parcial: " . formatMoney($amount) . ". Saldo " . formatMoney($newPrincipal) . " → novo empréstimo #{$newLoanId}");

                jsonResponse(['success' => true, 'message' =>
                    "Pagamento de " . formatMoney($amount) . " registado. " .
                    formatMoney($effectiveInterest) . " em juros liquidados; " . formatMoney($capitalRepaid) . " abatido ao capital. " .
                    "O saldo remanescente de " . formatMoney($newPrincipal) .
                    " foi convertido automaticamente num novo empréstimo (Empréstimo #" . $newLoanId .
                    "), com vencimento em " . formatDate($newDueDate) . "."
                ]);
            } else {
                // Fully paid
                $db->query("UPDATE loans SET status = 'paid' WHERE id = ?", [$loanId]);
                logActivity('loan_repaid', 'loan', $loanId, "Pagamento Final: " . formatMoney($amount));
                jsonResponse(['success' => true, 'message' => 'Empréstimo completamente liquidado. Parabéns!']);
            }
            break;
        }

        // ── Type: TOTAL ──────────────────────────────────────
        // Liquidar Total: pay interest + full remaining principal
        $totalDebt = $remainingPrincipal + $effectiveInterest;

        if ($amount < $totalDebt - 0.01) {
            jsonResponse(['success' => false, 'message' =>
                "Valor insuficiente para liquidação total. Total em dívida: " . formatMoney($totalDebt) .
                " (Capital: " . formatMoney($remainingPrincipal) . " + Juros: " . formatMoney($effectiveInterest) .
                ($scaled['overdue_periods'] > 0 ? " (" . number_format($scaled['rate'], 0) . "% — " . $scaled['overdue_periods'] . " período(s) em atraso)" : "") . ")."
            ], 422);
        }

        // Pay all pending interests
        foreach ($pendingInterests as $interest) {
            $db->query("UPDATE loan_interest SET status = 'paid', paid_date = ? WHERE id = ?", [$paidDate, $interest['id']]);
        }

        // If scaled interest > stored, record the extra interest
        if ($scaled['overdue_periods'] > 0 && $scaled['amount'] > $storedPendingInterest) {
            $extraInterest = $scaled['amount'] - $storedPendingInterest;
            $db->query(
                "INSERT INTO loan_interest (loan_id, member_id, cycle_id, reference_month, interest_rate, interest_amount, status, paid_date)
                 VALUES (?, ?, ?, ?, ?, ?, 'paid', ?)",
                [$loanId, $loan['member_id'], $cycleId, date('Y-m-01', strtotime($paidDate)),
                 $scaled['rate'], $extraInterest, $paidDate]
            );
        }

        // Record principal repayment
        $db->query(
            "INSERT INTO loan_repayments (loan_id, member_id, amount, paid_date, payment_method, receipt_ref)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$loanId, $loan['member_id'], $remainingPrincipal, $paidDate, $method, $receipt]
        );

        $db->query("UPDATE loans SET status = 'paid' WHERE id = ?", [$loanId]);
        logActivity('loan_repaid', 'loan', $loanId, "Liquidação Total: " . formatMoney($amount));

        jsonResponse(['success' => true, 'message' =>
            "Empréstimo de " . formatMoney((float)$loan['amount']) . " completamente liquidado. Total pago: " . formatMoney($amount) . "."
        ]);
        break;

    // ── PAY INTEREST ────────────────────────────────────────
    case 'pay_interest':
        $interestId = (int)($_POST['interest_id'] ?? 0);
        $paidDate   = $_POST['paid_date'] ?? date('Y-m-d');
        $db->query("UPDATE loan_interest SET status = 'paid', paid_date = ? WHERE id = ?", [$paidDate, $interestId]);
        logActivity('interest_paid', 'loan_interest', $interestId, "Juros pagos");
        jsonResponse(['success' => true, 'message' => 'Juros registados como pagos.']);
        break;

    // ── LIST INTEREST ───────────────────────────────────────
    case 'interests':
        $loanId = (int)($_GET['loan_id'] ?? 0);
        $interests = $db->fetchAll(
            "SELECT * FROM loan_interest WHERE loan_id = ? ORDER BY reference_month",
            [$loanId]
        );
        jsonResponse(['success' => true, 'data' => $interests]);
        break;

    // ── LOAN CAPACITY PREVIEW ──────────────────────────────
    case 'loan_capacity_preview':
        $previewMemberId = (int)($_GET['member_id'] ?? 0);
        if (!$previewMemberId) {
            jsonResponse(['success' => false, 'message' => 'member_id é obrigatório.'], 422);
        }

        $memberSavingsRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE member_id = ? AND cycle_id = ?",
            [$previewMemberId, $cycleId]
        );
        $memberSavings = (float)$memberSavingsRow['total'];

        $groupSavingsRow = $db->fetch(
            "SELECT COALESCE(SUM(c.amount), 0) as total FROM contributions c
             JOIN member_cycles mc ON mc.member_id = c.member_id AND mc.cycle_id = c.cycle_id
             WHERE c.cycle_id = ?",
            [$cycleId]
        );
        $groupSavings = (float)$groupSavingsRow['total'];

        $totalInterestRow = $db->fetch(
            "SELECT COALESCE(SUM(interest_amount), 0) as total FROM loan_interest WHERE cycle_id = ? AND status = 'paid'",
            [$cycleId]
        );
        $totalInterestPaid = (float)$totalInterestRow['total'];

        $interestShare = ($groupSavings > 0) ? ($memberSavings / $groupSavings) * $totalInterestPaid : 0.0;
        $tolerance     = (float)($cycle['loan_tolerance_margin'] ?? 0);

        $activeDebtRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM loans
             WHERE member_id = ? AND cycle_id = ? AND status IN ('active', 'overdue')",
            [$previewMemberId, $cycleId]
        );
        $memberActiveDebt = (float)$activeDebtRow['total'];
        $capacity = $memberSavings + $interestShare + $tolerance - $memberActiveDebt;

        jsonResponse([
            'success'  => true,
            'capacity' => round($capacity, 2),
            'breakdown' => [
                'member_savings' => round($memberSavings, 2),
                'interest_share' => round($interestShare, 2),
                'tolerance'      => round($tolerance, 2),
                'active_debt'    => round($memberActiveDebt, 2),
                'capacity'       => round($capacity, 2),
            ]
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
