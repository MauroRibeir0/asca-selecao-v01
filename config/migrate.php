<?php
/**
 * ASCA Selecção - Database Migration Script
 * Creates all 11 tables for the savings & loan system.
 * Run once via browser: http://localhost/.../config/migrate.php
 */

require_once __DIR__ . '/constants.php';

// Direct DB connection (can't use Database class yet—tables may not exist)
$host     = getenv('DB_HOST')  ?: 'localhost';
$dbname   = getenv('DB_NAME')  ?: 'asca_seleccao';
$username = getenv('DB_USER')  ?: 'root';
$password = getenv('DB_PASS')  ?: '';
$charset  = 'utf8mb4';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>ASCA Selecção — Migração da Base de Dados</h2><pre>";

try {
    // Create database if it doesn't exist
    $pdoNoDB = new PDO("mysql:host={$host};charset={$charset}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdoNoDB->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de dados '{$dbname}' verificada/criada.\n";

    // Connect to the database
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset={$charset}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 1. users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        username    VARCHAR(50)  NOT NULL UNIQUE,
        password    VARCHAR(255) NOT NULL,
        full_name   VARCHAR(150) NOT NULL,
        email       VARCHAR(150) NULL,
        role        ENUM('admin','user','member') DEFAULT 'user',
        member_id   INT          NULL,
        is_active   TINYINT(1)   DEFAULT 1,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'users' criada.\n";

    // 2. cycles
    $pdo->exec("CREATE TABLE IF NOT EXISTS cycles (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(50)  NOT NULL,
        start_date  DATE         NOT NULL,
        end_date    DATE         NOT NULL,
        joia_amount DECIMAL(12,2) NOT NULL DEFAULT 1000.00,
        joia_deadline DATE       NOT NULL,
        min_monthly DECIMAL(12,2) NOT NULL DEFAULT 2000.00,
        max_monthly DECIMAL(12,2) NOT NULL DEFAULT 5000.00,
        monthly_deadline_day INT NOT NULL DEFAULT 10,
        late_fee_pct DECIMAL(5,2) NOT NULL DEFAULT 15.00,
        loan_interest_pct DECIMAL(5,2) NOT NULL DEFAULT 15.00,
        loan_repayment_days INT NOT NULL DEFAULT 30,
        min_loan_movement DECIMAL(12,2) NOT NULL DEFAULT 50000.00,
        fixed_interest_entitlement DECIMAL(12,2) NOT NULL DEFAULT 7500.00,
        loan_tolerance_margin DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        allow_multiple_loans TINYINT(1) DEFAULT 1,
        is_active   TINYINT(1)   DEFAULT 1,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'cycles' criada.\n";

    // 3. members
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        full_name       VARCHAR(200) NOT NULL,
        phone           VARCHAR(20)  NULL,
        email           VARCHAR(150) NULL,
        id_number       VARCHAR(50)  NULL,
        address         VARCHAR(300) NULL,
        join_date       DATE         NOT NULL,
        status          ENUM('active','inactive','suspended') DEFAULT 'active',
        notes           TEXT         NULL,
        created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'members' criada.\n";

    // 4. member_cycles
    $pdo->exec("CREATE TABLE IF NOT EXISTS member_cycles (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        member_id   INT NOT NULL,
        cycle_id    INT NOT NULL,
        enrolled_at DATE NOT NULL,
        status      ENUM('active','inactive') DEFAULT 'active',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id)  REFERENCES cycles(id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (member_id, cycle_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'member_cycles' criada.\n";

    // 5. joias
    $pdo->exec("CREATE TABLE IF NOT EXISTS joias (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        member_id   INT NOT NULL,
        cycle_id    INT NOT NULL,
        amount      DECIMAL(12,2) NOT NULL DEFAULT 1000.00,
        paid_date   DATE         NULL,
        status      ENUM('pending','paid') DEFAULT 'pending',
        receipt_ref VARCHAR(50)  NULL,
        notes       TEXT         NULL,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id)  REFERENCES cycles(id) ON DELETE CASCADE,
        UNIQUE KEY unique_joia (member_id, cycle_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'joias' criada.\n";

    // 6. contributions
    $pdo->exec("CREATE TABLE IF NOT EXISTS contributions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        member_id       INT NOT NULL,
        cycle_id        INT NOT NULL,
        reference_month DATE NOT NULL,
        amount          DECIMAL(12,2) NOT NULL,
        paid_date       DATE         NOT NULL,
        due_date        DATE         NOT NULL,
        is_late         TINYINT(1)   DEFAULT 0,
        late_fee        DECIMAL(12,2) DEFAULT 0.00,
        payment_method  ENUM('cash','mpesa','bank_transfer') DEFAULT 'cash',
        receipt_ref     VARCHAR(50)  NULL,
        notes           TEXT         NULL,
        created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id)  REFERENCES cycles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'contributions' criada.\n";

    // 7. loans
    $pdo->exec("CREATE TABLE IF NOT EXISTS loans (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        member_id       INT NOT NULL,
        cycle_id        INT NOT NULL,
        amount          DECIMAL(12,2) NOT NULL,
        disbursement_date DATE NOT NULL,
        due_date        DATE NOT NULL,
        status          ENUM('active','paid','overdue','defaulted') DEFAULT 'active',
        notes           TEXT NULL,
        created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id)  REFERENCES cycles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'loans' criada.\n";

    // 8. loan_interest
    $pdo->exec("CREATE TABLE IF NOT EXISTS loan_interest (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        loan_id     INT NOT NULL,
        member_id   INT NOT NULL,
        cycle_id    INT NOT NULL,
        reference_month DATE NOT NULL,
        interest_rate   DECIMAL(5,2) NOT NULL DEFAULT 15.00,
        interest_amount DECIMAL(12,2) NOT NULL,
        paid_date   DATE         NULL,
        status      ENUM('pending','paid') DEFAULT 'pending',
        notes       TEXT         NULL,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (loan_id)   REFERENCES loans(id) ON DELETE CASCADE,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id)  REFERENCES cycles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'loan_interest' criada.\n";

    // 9. loan_repayments
    $pdo->exec("CREATE TABLE IF NOT EXISTS loan_repayments (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        loan_id     INT NOT NULL,
        member_id   INT NOT NULL,
        amount      DECIMAL(12,2) NOT NULL,
        paid_date   DATE         NOT NULL,
        payment_method ENUM('cash','mpesa','bank_transfer') DEFAULT 'cash',
        receipt_ref VARCHAR(50)  NULL,
        notes       TEXT         NULL,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (loan_id)   REFERENCES loans(id) ON DELETE CASCADE,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'loan_repayments' criada.\n";

    // 10. distributions
    $pdo->exec("CREATE TABLE IF NOT EXISTS distributions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        cycle_id        INT NOT NULL,
        member_id       INT NOT NULL,
        type            ENUM('interest','late_fee','surplus') NOT NULL,
        amount          DECIMAL(12,2) NOT NULL,
        description     TEXT NULL,
        distributed_at  DATE NOT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cycle_id)  REFERENCES cycles(id) ON DELETE CASCADE,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'distributions' criada.\n";

    // 11. activity_log
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NULL,
        action      VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50)  NULL,
        entity_id   INT          NULL,
        description TEXT         NULL,
        ip_address  VARCHAR(45)  NULL,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabela 'activity_log' criada.\n";

    // Add FK users.member_id
    try {
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_member
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL");
        echo "✅ FK users.member_id adicionada.\n";
    } catch (PDOException $e) {
        echo "ℹ️  FK users.member_id já existe ou erro ignorável.\n";
    }

    // Seed default admin
    $existing = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE username = 'admin'")->fetch();
    if ($existing['cnt'] == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, full_name, email, role)
            VALUES ('admin', ?, 'Administrador', 'admin@asca.co.mz', 'admin')")
            ->execute([$hash]);
        echo "✅ Utilizador admin criado (senha: admin123).\n";
    }

    // Seed default cycle
    $existingCycle = $pdo->query("SELECT COUNT(*) as cnt FROM cycles WHERE name = 'Ciclo 2025-2026'")->fetch();
    if ($existingCycle['cnt'] == 0) {
        $pdo->exec("INSERT INTO cycles (name, start_date, end_date, joia_deadline)
            VALUES ('Ciclo 2025-2026', '2025-12-01', '2026-11-30', '2025-12-30')");
        echo "✅ Ciclo 2025-2026 criado.\n";
    }

    // Patch columns
    $cols = $pdo->query("SHOW COLUMNS FROM cycles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('loan_tolerance_margin', $cols)) {
        $pdo->exec("ALTER TABLE cycles ADD COLUMN loan_tolerance_margin DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER fixed_interest_entitlement");
        echo "✅ Coluna 'loan_tolerance_margin' adicionada.\n";
    }
    if (!in_array('allow_multiple_loans', $cols)) {
        $pdo->exec("ALTER TABLE cycles ADD COLUMN allow_multiple_loans TINYINT(1) DEFAULT 1 AFTER loan_tolerance_margin");
        echo "✅ Coluna 'allow_multiple_loans' adicionada.\n";
    }

    echo "\n🎉 Migração concluída com sucesso!\n";

} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
echo "</pre>";
