<?php
/**
 * ASCA Selecção - Global Helper Functions
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Format currency value (e.g., 2.500,00 MT)
 */
function formatMoney(float $value): string {
    return number_format($value, 2, ',', '.') . ' ' . APP_CURRENCY;
}

/**
 * Format date to Portuguese format (dd/mm/yyyy)
 */
function formatDate(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

/**
 * Get month name in Portuguese from date string
 */
function formatMonthName(string $date): string {
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    $monthNum = (int)date('m', strtotime($date));
    return $months[$monthNum] ?? '—';
}

/**
 * Format datetime to Portuguese format
 */
function formatDateTime(?string $datetime): string {
    if (!$datetime) return '—';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION["flash_{$type}"] = $message;
}

/**
 * Get and clear flash message
 */
function getFlash(string $type): ?string {
    $key = "flash_{$type}";
    $msg = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $msg;
}

/**
 * Render flash messages as Bootstrap alerts
 */
function renderFlashMessages(): string {
    $html = '';
    $types = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    foreach ($types as $key => $bsClass) {
        $msg = getFlash($key);
        if ($msg) {
            $html .= '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show" role="alert">'
                . sanitize($msg)
                . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
    return $html;
}

/**
 * Get the active cycle (or selected cycle)
 */
function getActiveCycle(): ?array {
    $db = Database::getInstance();
    $cycleId = getSelectedCycleId();
    
    if ($cycleId) {
        $cycle = $db->fetch("SELECT * FROM cycles WHERE id = ?", [$cycleId]);
        if ($cycle) return $cycle;
    }

    // Fallback: most recent active cycle
    $cycle = $db->fetch("SELECT * FROM cycles WHERE is_active = 1 ORDER BY start_date DESC LIMIT 1");
    if ($cycle) {
        $_SESSION['selected_cycle_id'] = $cycle['id'];
    }
    return $cycle;
}

/**
 * Get all cycles for dropdown
 */
function getAllCycles(): array {
    $db = Database::getInstance();
    return $db->fetchAll("SELECT id, name, start_date, end_date, is_active FROM cycles ORDER BY start_date DESC");
}

/**
 * Check if member has paid jóia for a cycle
 */
function hasJoiaPaid(int $memberId, int $cycleId): bool {
    $db = Database::getInstance();
    $row = $db->fetch(
        "SELECT status FROM joias WHERE member_id = ? AND cycle_id = ? AND status = 'paid'",
        [$memberId, $cycleId]
    );
    return $row !== false;
}

/**
 * Get total loan amount for a member in a cycle (for minimum movement check)
 */
function getMemberLoanMovement(int $memberId, int $cycleId): float {
    $db = Database::getInstance();
    $row = $db->fetch(
        "SELECT COALESCE(SUM(amount), 0) as total FROM loans WHERE member_id = ? AND cycle_id = ?",
        [$memberId, $cycleId]
    );
    return (float)$row['total'];
}

/**
 * Calculate due date for a contribution (10th of next month)
 */
function calculateContributionDueDate(string $referenceMonth): string {
    $dt = new DateTime($referenceMonth);
    $dt->modify('+1 month');
    $day = DEFAULT_MONTHLY_DEADLINE_DAY;
    $maxDay = (int)$dt->format('t');
    $day = min($day, $maxDay);
    return $dt->format("Y-m-{$day}");
}

/**
 * Calculate loan due date (+30 days from disbursement)
 */
function calculateLoanDueDate(string $disbursementDate, int $days = DEFAULT_LOAN_REPAYMENT_DAYS): string {
    $dt = new DateTime($disbursementDate);
    $dt->modify("+{$days} days");
    return $dt->format('Y-m-d');
}

/**
 * Log an activity
 */
function logActivity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): void {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $db->query(
        "INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $action, $entityType, $entityId, $description, $ip]
    );
}

/**
 * Get month name in Portuguese
 */
function monthNamePt(int $month): string {
    $names = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $names[$month] ?? '';
}

/**
 * Send JSON response (for API endpoints)
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validate required fields from $_POST
 */
function validateRequired(array $fields): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty($_POST[$field]) && $_POST[$field] !== '0') {
            $errors[] = "O campo '{$label}' é obrigatório.";
        }
    }
    return $errors;
}

/**
 * Check if a field value is unique in a table
 */
function isUnique(string $table, string $field, $value, ?int $excludeId = null): bool {
    $db = Database::getInstance();
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$field} = ?";
    $params = [$value];
    
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $row = $db->fetch($sql, $params);
    return (int)($row['count'] ?? 0) === 0;
}

/**
 * Validate member specific unique fields
 */
function validateMemberUnicidade(array $data, ?int $excludeId = null): array {
    $errors = [];
    $fields = [
        'full_name' => 'Nome Completo',
        'email'     => 'Email',
        'phone'     => 'Telefone',
        'id_number' => 'Documento (BI/NUIT)'
    ];

    foreach ($fields as $field => $label) {
        if (!empty($data[$field]) && !isUnique('members', $field, $data[$field], $excludeId)) {
            $errors[] = "Este {$label} já está registado no sistema.";
        }
    }
    return $errors;
}

/**
 * Badge HTML for status
 */
function statusBadge(string $status): string {
    $map = [
        'active'    => ['bg-success', 'Activo'],
        'inactive'  => ['bg-secondary', 'Inactivo'],
        'suspended' => ['bg-warning text-dark', 'Suspenso'],
        'paid'      => ['bg-success', 'Pago'],
        'pending'   => ['bg-warning text-dark', 'Pendente'],
        'overdue'   => ['bg-danger', 'Em Atraso'],
        'defaulted' => ['bg-dark', 'Incumprido'],
    ];
    $cfg = $map[$status] ?? ['bg-secondary', ucfirst($status)];
    return '<span class="badge ' . $cfg[0] . '">' . $cfg[1] . '</span>';
}
