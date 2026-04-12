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

    public function getConnection()
    {
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
                $this->conn = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
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
