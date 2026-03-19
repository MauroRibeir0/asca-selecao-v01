<?php
/**
 * ASCA Selecção - Distributions API
 *
 * Handles cycle-end distribution calculations and execution.
 *
 * Business rules:
 * - eligible = member total loan movement >= cycle['min_loan_movement'] (50,000 MT)
 * - fixed_entitlement = cycle['fixed_interest_entitlement'] (7,500 MT)
 * - IF total_interest >= (fixed_entitlement × eligible_count):
 *     each eligible gets fixed_entitlement; surplus distributed proportionally to ALL members
 * - ELSE: prorate fixed_entitlement among eligible members only; no surplus
 * - Late fees: proportional to savings share among ALL members
 * - Capital return: member's total contributions (informational, NOT stored)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db     = Database::getInstance();
$cycle  = getActiveCycle();
$action = $_GET['action'] ?? $_POST['action'] ?? 'preview';

if (!$cycle) {
    jsonResponse(['success' => false, 'message' => 'Nenhum ciclo activo.'], 404);
}
$cycleId = $cycle['id'];

/**
 * Build distribution data for all enrolled active members.
 * Returns ['members' => [...], 'summary' => [...]]
 */
function buildDistributionData(array $cycle, int $cycleId, object $db): array {
    $fixedEntitlement = (float)($cycle['fixed_interest_entitlement'] ?? 7500);
    $minMovement      = (float)($cycle['min_loan_movement'] ?? 50000);

    // All enrolled active members
    $members = $db->fetchAll(
        "SELECT m.id, m.full_name
         FROM member_cycles mc
         JOIN members m ON m.id = mc.member_id
         WHERE mc.cycle_id = ? AND m.status = 'active'
         ORDER BY m.full_name",
        [$cycleId]
    );

    // Group totals
    $groupSavingsRow = $db->fetch(
        "SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE cycle_id = ?",
        [$cycleId]
    );
    $groupSavings = (float)$groupSavingsRow['total'];

    $totalInterestRow = $db->fetch(
        "SELECT COALESCE(SUM(interest_amount), 0) as total FROM loan_interest WHERE cycle_id = ? AND status = 'paid'",
        [$cycleId]
    );
    $totalInterest = (float)$totalInterestRow['total'];

    $totalLateFeesRow = $db->fetch(
        "SELECT COALESCE(SUM(late_fee), 0) as total FROM contributions WHERE cycle_id = ?",
        [$cycleId]
    );
    $totalLateFees = (float)$totalLateFeesRow['total'];

    // Already distributed?
    $distRow = $db->fetch(
        "SELECT COUNT(*) as cnt FROM distributions WHERE cycle_id = ?",
        [$cycleId]
    );
    $alreadyDistributed = (int)($distRow['cnt'] ?? 0) > 0;

    // Build per-member data
    $eligibleCount = 0;
    $membersData   = [];

    foreach ($members as $m) {
        $memberId = (int)$m['id'];

        // Savings
        $savRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE member_id = ? AND cycle_id = ?",
            [$memberId, $cycleId]
        );
        $memberSavings = (float)$savRow['total'];

        // Loan movement (ALL loans, any status, for eligibility)
        $movRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM loans WHERE member_id = ? AND cycle_id = ?",
            [$memberId, $cycleId]
        );
        $totalMovement = (float)$movRow['total'];

        $isEligible = $totalMovement >= $minMovement;
        if ($isEligible) $eligibleCount++;

        $membersData[] = [
            'id'            => $memberId,
            'full_name'     => $m['full_name'],
            'total_savings' => $memberSavings,
            'total_movement'=> $totalMovement,
            'is_eligible'   => $isEligible,
            // Placeholders — computed below
            'interest_fixed'   => 0.0,
            'interest_surplus' => 0.0,
            'late_fee_share'   => 0.0,
            'capital_return'   => $memberSavings,
            'total_to_receive' => 0.0,
        ];
    }

    // Compute interest distribution
    $totalFixedNeeded = $fixedEntitlement * $eligibleCount;
    $hasSurplus       = $totalInterest >= $totalFixedNeeded;
    $surplusAmount    = $hasSurplus ? $totalInterest - $totalFixedNeeded : 0.0;

    foreach ($membersData as &$m) {
        $savShare = ($groupSavings > 0) ? ($m['total_savings'] / $groupSavings) : 0.0;

        // Fixed interest
        if ($m['is_eligible']) {
            if ($hasSurplus) {
                $m['interest_fixed'] = $fixedEntitlement;
            } else {
                // Prorate among eligible
                $m['interest_fixed'] = ($eligibleCount > 0)
                    ? round($totalInterest / $eligibleCount, 2)
                    : 0.0;
            }
        }

        // Surplus interest (proportional to ALL members)
        $m['interest_surplus'] = $hasSurplus
            ? round($savShare * $surplusAmount, 2)
            : 0.0;

        // Late fee share (proportional to ALL members)
        $m['late_fee_share'] = round($savShare * $totalLateFees, 2);

        // Total to receive = fixed_interest + surplus + late_fee + capital_return
        $m['total_to_receive'] = round(
            $m['interest_fixed'] + $m['interest_surplus'] + $m['late_fee_share'] + $m['capital_return'],
            2
        );
    }
    unset($m);

    $summary = [
        'total_interest'       => $totalInterest,
        'total_late_fees'      => $totalLateFees,
        'group_savings'        => $groupSavings,
        'eligible_count'       => $eligibleCount,
        'total_fixed_needed'   => $totalFixedNeeded,
        'has_surplus'          => $hasSurplus,
        'surplus_amount'       => $surplusAmount,
        'already_distributed'  => $alreadyDistributed,
        'fixed_entitlement'    => $fixedEntitlement,
        'min_movement'         => $minMovement,
    ];

    return ['members' => $membersData, 'summary' => $summary];
}

switch ($action) {

    // ── PREVIEW ───────────────────────────────────────────────
    case 'preview':
        $data = buildDistributionData($cycle, $cycleId, $db);
        jsonResponse(['success' => true] + $data);
        break;

    // ── STATUS ────────────────────────────────────────────────
    case 'status':
        $distRow = $db->fetch(
            "SELECT COUNT(*) as cnt, MIN(distributed_at) as dist_date FROM distributions WHERE cycle_id = ?",
            [$cycleId]
        );
        $distributed = (int)($distRow['cnt'] ?? 0) > 0;
        jsonResponse([
            'success'      => true,
            'distributed'  => $distributed,
            'dist_date'    => $distRow['dist_date'] ?? null,
        ]);
        break;

    // ── HISTORY ───────────────────────────────────────────────
    case 'history':
        $rows = $db->fetchAll(
            "SELECT d.*, m.full_name
             FROM distributions d
             JOIN members m ON m.id = d.member_id
             WHERE d.cycle_id = ?
             ORDER BY d.member_id, d.type",
            [$cycleId]
        );
        jsonResponse(['success' => true, 'data' => $rows]);
        break;

    // ── DISTRIBUTE ────────────────────────────────────────────
    case 'distribute':
        requireRole(ROLE_ADMIN);

        // Check if already distributed
        $distRow = $db->fetch(
            "SELECT COUNT(*) as cnt FROM distributions WHERE cycle_id = ?",
            [$cycleId]
        );
        if ((int)($distRow['cnt'] ?? 0) > 0) {
            jsonResponse([
                'success' => false,
                'message' => 'A distribuição para este ciclo já foi executada.',
            ], 422);
        }

        $data = buildDistributionData($cycle, $cycleId, $db);
        $members = $data['members'];
        $summary = $data['summary'];

        $now = date('Y-m-d H:i:s');
        $insertedRows = 0;

        foreach ($members as $m) {
            $memberId = $m['id'];

            // Fixed interest (eligible only)
            if ($m['interest_fixed'] > 0) {
                $db->query(
                    "INSERT INTO distributions (cycle_id, member_id, type, amount, description, distributed_at)
                     VALUES (?, ?, 'interest', ?, ?, ?)",
                    [
                        $cycleId, $memberId, $m['interest_fixed'],
                        'Juros fixos de ciclo' . ($m['is_eligible'] ? ' (membro elegível)' : ''),
                        $now
                    ]
                );
                $insertedRows++;
            }

            // Surplus interest
            if ($m['interest_surplus'] > 0) {
                $db->query(
                    "INSERT INTO distributions (cycle_id, member_id, type, amount, description, distributed_at)
                     VALUES (?, ?, 'surplus', ?, ?, ?)",
                    [
                        $cycleId, $memberId, $m['interest_surplus'],
                        'Excedente de juros (proporcional às poupanças)',
                        $now
                    ]
                );
                $insertedRows++;
            }

            // Late fees share
            if ($m['late_fee_share'] > 0) {
                $db->query(
                    "INSERT INTO distributions (cycle_id, member_id, type, amount, description, distributed_at)
                     VALUES (?, ?, 'late_fee', ?, ?, ?)",
                    [
                        $cycleId, $memberId, $m['late_fee_share'],
                        'Quota-parte de multas (proporcional às poupanças)',
                        $now
                    ]
                );
                $insertedRows++;
            }
        }

        logActivity('cycle_distribution', 'cycle', $cycleId,
            "Distribuição de ciclo executada. {$insertedRows} registos inseridos. " .
            "Juros totais: " . formatMoney($summary['total_interest']) . ". " .
            "Multas: " . formatMoney($summary['total_late_fees']) . ". " .
            "Membros elegíveis: {$summary['eligible_count']}."
        );

        jsonResponse([
            'success' => true,
            'message' => "Distribuição executada com sucesso. {$insertedRows} registos criados.",
            'summary' => $summary,
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
