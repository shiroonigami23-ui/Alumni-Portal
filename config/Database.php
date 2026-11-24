<?php
class Database
{
    private $driver;
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->driver = strtolower((string)(getenv('DB_DRIVER') ?: 'pgsql'));
        // Use environment variables or fallback to localhost defaults
        // Force 127.0.0.1 to avoid IPv6 resolution issues on Windows
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $defaultPort = $this->driver === 'mysql' ? '3306' : '5432';
        $this->port = getenv('DB_PORT') ?: $defaultPort;
        $this->db_name = getenv('DB_NAME') ?: 'alumni_portal';
        $this->username = getenv('DB_USER') ?: ($this->driver === 'mysql' ? 'root' : 'postgres');
        $this->password = getenv('DB_PASSWORD') ?: ''; // Empty by default for local trust auth
    }

    /** One shared connection per request (models/APIs call getConnection() repeatedly). */
    private static array $pool = [];

    public function getConnection()
    {
        $poolKey = $this->driver . '|' . $this->host . '|' . $this->port . '|' . $this->db_name . '|' . $this->username;
        if (isset(self::$pool[$poolKey])) {
            return $this->conn = self::$pool[$poolKey];
        }
        $this->conn = null;

        $ports = [$this->port];
        // Local fallback for XAMPP PostgreSQL when another service owns 5432.
        if ($this->driver === 'pgsql' && $this->port === '5432') {
            $ports[] = '5433';
        }

        $lastException = null;
        foreach ($ports as $port) {
            try {
                if ($this->driver === 'mysql') {
                    $dsn = "mysql:host=" . $this->host . ";port=" . $port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                } else {
                    $dsn = "pgsql:host=" . $this->host . ";port=" . $port . ";dbname=" . $this->db_name;
                }
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                    // Reuse server connections between requests (set DB_PERSISTENT=false to disable)
                    PDO::ATTR_PERSISTENT => strtolower((string)(getenv('DB_PERSISTENT') ?: 'true')) !== 'false',
                ];
                if ($this->driver !== 'mysql' && defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
                    // One network round trip per query (no PREPARE/DEALLOCATE), still fully parameterised
                    $options[PDO::PGSQL_ATTR_DISABLE_PREPARES] = true;
                }
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                if ($this->driver !== 'mysql') {
                    // All timestamps are stored/compared in UTC (the frontend formats them to IST).
                    // Without this, a server running in local time would shift every date by its offset.
                    $this->conn->exec("SET TIME ZONE 'UTC'");
                }
                if ($this->conn->inTransaction()) {
                    $this->conn->rollBack(); // never inherit a half-finished transaction from a reused connection
                }
                self::$pool[$poolKey] = $this->conn;
                break;
            } catch (PDOException $exception) {
                $lastException = $exception;
                $this->conn = null;
            }
        }

        if ($this->conn === null && $lastException !== null) {
            error_log("Connection error: " . $lastException->getMessage());
        }

        return $this->conn;
    }
}
