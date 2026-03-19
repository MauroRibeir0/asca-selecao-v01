<?php
/**
 * ASCA Selecção - Diagnostic Script
 * Use this to verify environment on production.
 * DELETE THIS FILE AFTER USE.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>ASCA Selecção - Debug Diagnostics</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow: auto; }
    </style>
</head>
<body>
    <h1>System Diagnostics</h1>

    <div class="section">
        <h2>Environment & Paths</h2>
        <ul>
            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
            <li><strong>OS:</strong> <?php echo PHP_OS; ?></li>
            <li><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
            <li><strong>Current Path:</strong> <?php echo __DIR__; ?></li>
            <li><strong>BASE_URL:</strong> <?php echo BASE_URL; ?></li>
        </ul>
    </div>

    <div class="section">
        <h2>Session Status</h2>
        <pre><?php
            if (session_status() === PHP_SESSION_NONE) {
                echo "Session: NOT STARTED\n";
                session_start();
            }
            echo "Session ID: " . session_id() . "\n";
            echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
            echo "Session Data: \n";
            print_r($_SESSION);
        ?></pre>
    </div>

    <div class="section">
        <h2>Database Connectivity</h2>
        <?php
        try {
            $db = Database::getInstance();
            echo '<p class="success">Database: CONNECTED SUCCESSFULY</p>';
            
            // Check essential tables
            $tables = ['users', 'members', 'cycles', 'contributions', 'loans'];
            echo "<ul>";
            foreach ($tables as $table) {
                try {
                    $count = $db->fetch("SELECT COUNT(*) as t FROM $table")['t'];
                    echo "<li>Table <code>$table</code>: <span class='success'>EXISTS</span> ($count records)</li>";
                } catch (Exception $e) {
                    echo "<li>Table <code>$table</code>: <span class='error'>NOT FOUND or ERROR: " . $e->getMessage() . "</span></li>";
                }
            }
            echo "</ul>";
        } catch (Exception $e) {
            echo '<p class="error">Database Connection FAILED: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>PHP Info Summary</h2>
        <ul>
            <li><strong>memory_limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
            <li><strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?></li>
            <li><strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
            <li><strong>max_execution_time:</strong> <?php echo ini_get('max_execution_time'); ?></li>
            <li><strong>display_errors:</strong> <?php echo ini_get('display_errors'); ?></li>
            <li><strong>error_reporting:</strong> <?php echo ini_get('error_reporting'); ?></li>
        </ul>
    </div>

    <p><small>Warning: Please delete this file manualy after verification.</small></p>
</body>
</html>
