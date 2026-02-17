<?php
include_once '../models/Session.php';

class Auth {
    private $db;
    private $session;

    public function __construct($db) {
        $this->db = $db;
        $this->session = new Session($db);
    }

    public function validateRequest() {
        // 1. Get Headers
        $headers = null;
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER; 
        }

        // 2. Extract Token
        $token = null;
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // 3. Reject if missing
        if (!$token) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized. No token provided."));
            exit();
        }

        // 4. Validate against Session Table
        $user_session = $this->session->isValid($token);
        if (!$user_session) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized. Invalid or expired token."));
            exit();
        }

        $user_id = $user_session['user_id'];

        // 5. STATUS CHECK: Permanent Ban & Suspension (Section 6)
        $u_stmt = $this->db->prepare("SELECT status, suspension_expires_at FROM users WHERE user_id = :uid");
        $u_stmt->execute(['uid' => $user_id]);
        $user = $u_stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['status'] === 'banned') {
            http_response_code(403);
            echo json_encode(["message" => "Account is permanently banned."]);
            exit();
        }

        if ($user['status'] === 'suspended' && strtotime($user['suspension_expires_at']) > time()) {
            http_response_code(403);
            echo json_encode(["message" => "Account is suspended until " . $user['suspension_expires_at']]);
            exit();
        }

        // 6. SHADOW BAN CHECK: Malicious Reporting (Section 6)
try {
    $s_stmt = $this->db->prepare("SELECT shadow_ban_until FROM moderation_strikes WHERE user_id = :uid");
    $s_stmt->execute(['uid' => $user_id]);
    $shadow_ban = $s_stmt->fetchColumn();

    if ($shadow_ban && strtotime($shadow_ban) > time()) {
        // Only block "POST" or "PUT" requests for shadow-banned users
        // They can still view (GET), but cannot interact.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            http_response_code(403);
            echo json_encode([
                "message" => "You are shadow banned until $shadow_ban for malicious reporting.",
                "is_shadow_banned" => true
            ]);
            exit();
        }
    }
} catch (Exception $e) {
    // moderation_strikes table might not exist, ignore
    error_log("Moderation strikes check failed: " . $e->getMessage());
}

        return $user_id;
    }

    public function logAction($user_id, $action, $details) {
        $query = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (:uid, :action, :details, :ip)";
        $stmt = $this->db->prepare($query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->execute(['uid' => $user_id, 'action' => $action, 'details' => $details, 'ip' => $ip]);

        $log_entry = "[" . date('Y-m-d H:i:s') . "] User ID: $user_id | Action: $action | Details: $details | IP: $ip" . PHP_EOL;
        $log_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'log.txt';
        if (!file_exists(dirname($log_file))) { mkdir(dirname($log_file), 0777, true); }
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}