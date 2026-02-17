<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $email;
    public $password;
    public $role;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. REGISTER NEW USER
   public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  (email, password_hash, role, status)
                  VALUES
                  (:email, :password_hash, :role, :status)
                  RETURNING user_id";

        $stmt = $this->conn->prepare($query);

        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // ARCHITECT LOGIC: Students = active, others = pending
        $status = ($this->role === 'student') ? 'active' : 'pending';

        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":status", $status);

        if($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->user_id = $row['user_id'];
            return true;
        }
        return false;
    }

    // 2. CHECK IF EMAIL EXISTS
    public function emailExists() {
        $query = "SELECT user_id, password_hash, role, status
                  FROM " . $this->table_name . "
                  WHERE email = :email
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // 3. LOGIN VERIFICATION (New)
    public function login() {
        $query = "SELECT user_id, password_hash, role, status
                  FROM " . $this->table_name . "
                  WHERE email = :email
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify Password
            if(password_verify($this->password, $row['password_hash'])) {
                $this->user_id = $row['user_id'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                return true;
            }
        }
        return false;
    }
}
?>