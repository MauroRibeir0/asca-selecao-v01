<?php
/**
 * ASCA Selecção - Database Connection Test
 * Use this script to verify credentials on production.
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h2>ASCA Selecção - Diagnóstico de Base de Dados</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9;'>";
    echo "<strong>✅ Sucesso!</strong> Ligação estabelecida com a base de dados.<br>";
    echo "</div>";

    // Test tables
    $tables = $db->fetchAll("SHOW TABLES");
    if (count($tables) > 0) {
        echo "<p>Base de Dados configurada e com tabelas encontradas.</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Ligação OK, mas a base de dados está vazia. Por favor, execute o script de migração: <a href='migrate.php'>migrate.php</a></p>";
    }

} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffebee;'>";
    echo "<strong>❌ Erro de Ligação:</strong> " . $e->getMessage() . "<br><br>";
    echo "Verifique se o Host, Nome da Base de Dados, Utilizador e Password estão correctos em <code>config/database.php</code>.";
    echo "</div>";
}
