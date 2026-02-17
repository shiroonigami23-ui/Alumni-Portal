<?php
class Session {
    private $conn;
    private $table_name = "sessions";

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE NEW SESSION
    public function create($user_id, $session_token, $expires_at) {
        $query = "INSERT INTO " . $this->table_name . "
                  (user_id, session_token, ip_address, user_agent, expires_at)
                  VALUES
                  (:user_id, :session_token, :ip_address, :user_agent, :expires_at)";

        $stmt = $this->conn->prepare($query);

        // Capture Basic Device Info
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Bind Data
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":session_token", $session_token);
        $stmt->bindParam(":ip_address", $ip_address);
        $stmt->bindParam(":user_agent", $user_agent);
        $stmt->bindParam(":expires_at", $expires_at);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // CHECK IF SESSION IS VALID
    public function isValid($token) {
        // We select the record first to debug if it exists
        $query = "SELECT user_id, expires_at FROM " . $this->table_name . "
                  WHERE session_token = :token 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check expiry using PHP's time (more reliable in local dev)
            $expires = strtotime($row['expires_at']);
            if($expires > time()) {
                return $row;
            }
        }
        return false;
    }
}
?>