<?php
class Stream {
    private $conn;
    private $table = 'live_streams';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Start a stream session
    public function start($user_id, $title, $description) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'live', title = :title, description = :description, started_at = CURRENT_TIMESTAMP 
                  WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            'title' => $title,
            'description' => $description,
            'user_id' => $user_id
        ]);
    }

    // End a stream session
    public function end($user_id) {
        $query = "UPDATE " . $this->table . " SET status = 'offline' WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['user_id' => $user_id]);
    }
}