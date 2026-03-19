<?php
/**
 * ASCA Selecção - Database Connection (PDO Singleton)
 */

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset = 'utf8mb4';

    private function __construct() {
        // Automatically detect environment
        // Detect if running locally (XAMPP) or on production.
        $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
        $isLocal = (strpos($hostHeader, 'localhost') !== false || $hostHeader === '127.0.0.1');
        
        if ($isLocal) {
            // Local environment defaults (can be overridden by env vars)
            $this->host     = getenv('DB_HOST') ?: '127.0.0.1';
            $this->dbname   = getenv('DB_NAME') ?: 'asca_seleccao';
            $this->username = getenv('DB_USER') ?: 'root';
            $this->password = getenv('DB_PASS') ?: '';
        } else {
            // Production environment defaults (cPanel)
            $this->host     = getenv('DB_HOST') ?: 'localhost';
            $this->dbname   = getenv('DB_NAME') ?: 'fdlmoz_ascaselecdb';
            $this->username = getenv('DB_USER') ?: 'fdlmoz_ascselecusr';
            $this->password = getenv('DB_PASS') ?: 'M_ym9.@r4HdbMpET';
        }
        
        // If a port is specified in DB_HOST (e.g., localhost:3306), split it.
        if (strpos($this->host, ':') !== false) {
            [$hostPart, $portPart] = explode(':', $this->host, 2);
            $this->host = $hostPart;
            $this->port = $portPart;
        } else {
            $this->port = '3306'; // default MySQL port
        }
        
        // Build DSN with optional port
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("Erro de conexão à base de dados: " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    /**
     * Helper: execute a prepared statement and return it
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Helper: fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Helper: fetch single row
     */
    public function fetch(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Helper: get last insert ID
     */
    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
