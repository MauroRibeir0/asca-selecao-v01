<?php
/**
 * ASCA Selecção - Export API
 * Exports data to CSV
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$db = Database::getInstance();
$type = $_GET['type'] ?? 'members';
$cycle = getActiveCycle();

switch ($type) {
    case 'members':
        $filename = "membros_" . date('Ymd_His') . ".csv";
        $data = $db->fetchAll("SELECT full_name, phone, email, id_number, status, join_date FROM members ORDER BY full_name");
        $headers = ['Nome Completo', 'Telefone', 'Email', 'Documento', 'Status', 'Data Adesão'];
        break;

    case 'contributions':
        $cycleId = $cycle ? $cycle['id'] : 0;
        $filename = "contribuicoes_" . ($cycle ? $cycle['name'] : 'geral') . "_" . date('Ymd_His') . ".csv";
        $data = $db->fetchAll(
            "SELECT m.full_name, c.reference_month, c.amount, c.payment_date, c.payment_method, c.receipt_ref 
             FROM contributions c 
             JOIN members m ON c.member_id = m.id 
             WHERE c.cycle_id = ? 
             ORDER BY c.payment_date DESC",
            [$cycleId]
        );
        $headers = ['Membro', 'Mês Referência', 'Valor', 'Data Pagamento', 'Método', 'Ref. Recibo'];
        break;

    default:
        die("Tipo de exportação inválido.");
}

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, $headers, ';');

// Add data
foreach ($data as $row) {
    if (isset($row['amount'])) $row['amount'] = number_format($row['amount'], 2, ',', '.');
    fputcsv($output, array_values($row), ';');
}

fclose($output);
exit;
