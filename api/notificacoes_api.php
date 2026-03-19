<?php
/**
 * ASCA Selecção - Notifications API
 *
 * Returns real-time notifications for the logged-in admin/user.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db     = Database::getInstance();
$cycle  = getActiveCycle();
$action = $_GET['action'] ?? 'list';

if (!$cycle) {
    jsonResponse(['success' => true, 'notifications' => [], 'total_count' => 0]);
}
$cycleId = $cycle['id'];

switch ($action) {

    // ── LIST NOTIFICATIONS ─────────────────────────────────
    case 'list':
        $notifications = [];

        // 1. Overdue loans — urgency: danger
        // Auto-update overdue status first
        $db->query(
            "UPDATE loans SET status = 'overdue'
             WHERE status = 'active' AND due_date < CURDATE() AND cycle_id = ?",
            [$cycleId]
        );

        $overdueRow = $db->fetch(
            "SELECT COUNT(*) as cnt FROM loans WHERE cycle_id = ? AND status = 'overdue'",
            [$cycleId]
        );
        $overdueCount = (int)($overdueRow['cnt'] ?? 0);
        if ($overdueCount > 0) {
            $notifications[] = [
                'type'    => 'overdue_loans',
                'title'   => 'Empréstimos em Atraso',
                'message' => "{$overdueCount} empréstimo(s) com prestação em atraso.",
                'count'   => $overdueCount,
                'urgency' => 'danger',
                'icon'    => 'bi-exclamation-circle-fill',
            ];
        }

        // 2. Pending joias — urgency: warning
        // Members enrolled in cycle without paid jóia
        $pendingJoiasRow = $db->fetch(
            "SELECT COUNT(*) as cnt
             FROM member_cycles mc
             JOIN members m ON m.id = mc.member_id
             WHERE mc.cycle_id = ?
               AND m.status = 'active'
               AND NOT EXISTS (
                   SELECT 1 FROM joias j
                   WHERE j.member_id = mc.member_id
                     AND j.cycle_id  = mc.cycle_id
                     AND j.status    = 'paid'
               )",
            [$cycleId]
        );
        $pendingJoiasCount = (int)($pendingJoiasRow['cnt'] ?? 0);
        if ($pendingJoiasCount > 0) {
            $notifications[] = [
                'type'    => 'pending_joias',
                'title'   => 'Jóias Pendentes',
                'message' => "{$pendingJoiasCount} membro(s) com jóia por pagar.",
                'count'   => $pendingJoiasCount,
                'urgency' => 'warning',
                'icon'    => 'bi-gem',
            ];
        }

        // 3. Pending contributions — members without contribution for current month — urgency: warning
        $currentMonth = date('Y-m-01');
        $pendingContribRow = $db->fetch(
            "SELECT COUNT(*) as cnt
             FROM member_cycles mc
             JOIN members m ON m.id = mc.member_id
             WHERE mc.cycle_id = ?
               AND m.status = 'active'
               AND NOT EXISTS (
                   SELECT 1 FROM contributions c
                   WHERE c.member_id      = mc.member_id
                     AND c.cycle_id       = mc.cycle_id
                     AND c.reference_month = ?
               )",
            [$cycleId, $currentMonth]
        );
        $pendingContribCount = (int)($pendingContribRow['cnt'] ?? 0);
        if ($pendingContribCount > 0) {
            $monthLabel = date('F Y'); // will be in English; format nicely below
            $dt = new DateTime($currentMonth);
            $ptMonths = [
                1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',
                5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',
                9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
            ];
            $monthLabel = $ptMonths[(int)$dt->format('n')] . ' ' . $dt->format('Y');

            $notifications[] = [
                'type'    => 'pending_contributions',
                'title'   => 'Mensalidades em Falta',
                'message' => "{$pendingContribCount} membro(s) sem contribuição registada para {$monthLabel}.",
                'count'   => $pendingContribCount,
                'urgency' => 'warning',
                'icon'    => 'bi-cash-coin',
            ];
        }

        // 4. Loans due in the next 7 days — urgency: info
        $dueSoonRow = $db->fetch(
            "SELECT COUNT(*) as cnt FROM loans
             WHERE cycle_id = ?
               AND status IN ('active')
               AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            [$cycleId]
        );
        $dueSoonCount = (int)($dueSoonRow['cnt'] ?? 0);
        if ($dueSoonCount > 0) {
            $notifications[] = [
                'type'    => 'due_soon',
                'title'   => 'Vencimentos Próximos',
                'message' => "{$dueSoonCount} empréstimo(s) vencem nos próximos 7 dias.",
                'count'   => $dueSoonCount,
                'urgency' => 'info',
                'icon'    => 'bi-calendar-event',
            ];
        }

        $totalCount = array_sum(array_column($notifications, 'count'));

        jsonResponse([
            'success'      => true,
            'notifications' => $notifications,
            'total_count'  => $totalCount,
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Acção inválida.'], 400);
}
