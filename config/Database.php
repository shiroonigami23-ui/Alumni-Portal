<?php
class Database
{
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        // Use environment variables or fallback to localhost defaults
        // Force 127.0.0.1 to avoid IPv6 resolution issues on Windows
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->port = getenv('DB_PORT') ?: '5432';
        $this->db_name = getenv('DB_NAME') ?: 'alumni_portal';
        $this->username = getenv('DB_USER') ?: 'postgres';
        $this->password = getenv('DB_PASSWORD') ?: ''; // Empty by default for local trust auth
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $exception) {
            // Log error instead of echoing in production
            error_log("Connection error: " . $exception->getMessage());
            // Do NOT echo error to avoid breaking JSON responses
            // echo "Connection error: " . $exception->getMessage(); 
        }

        return $this->conn;
    }
}
