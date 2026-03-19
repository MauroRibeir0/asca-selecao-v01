<?php
/**
 * Rename max_loan_amount to loan_tolerance_margin
 */
require_once __DIR__ . '/constants.php';
header('Content-Type: text/html; charset=utf-8');
echo "<pre>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=asca_seleccao;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("ALTER TABLE cycles CHANGE max_loan_amount loan_tolerance_margin DECIMAL(12,2) NOT NULL DEFAULT 0.00");
    echo "✅ Coluna renomeada: max_loan_amount → loan_tolerance_margin\n";
} catch (PDOException $e) {
    echo "⚠️ " . $e->getMessage() . "\n";
}
echo "</pre>";
