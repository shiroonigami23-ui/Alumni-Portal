<?php
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../config/DbCompat.php';

class Auth {
    private $db;
    private $session;

    public function __construct($db) {
        $this->db = $db;
        $this->session = new Session($db);
    }

    public function validateRequest() {
        if (!$this->db) {
            http_response_code(503);
            echo json_encode(array("message" => "Database unavailable."));
            exit();
        }

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

        // 6. SHADOW BAN CHECK: reported abuse threshold
        try {
            $s_stmt = $this->db->prepare("SELECT shadow_ban_until FROM moderation_strikes WHERE user_id = :uid");
            $s_stmt->execute(['uid' => $user_id]);
            $shadow_ban = $s_stmt->fetchColumn();

            if ($shadow_ban && strtotime((string)$shadow_ban) > time()) {
                $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
                $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
                $readBlockedScripts = ['get_feed.php', 'public_feed.php', 'search_posts.php', 'get_user_replies.php'];

                if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) || ($method === 'GET' && in_array($script, $readBlockedScripts, true))) {
                    http_response_code(403);
                    echo json_encode([
                        "message" => "Your account is shadow banned until $shadow_ban.",
                        "is_shadow_banned" => true
                    ]);
                    exit();
                }
            }
        } catch (Exception $e) {
            // moderation_strikes table might not exist, ignore
            error_log("Moderation strikes check failed: " . $e->getMessage());
        }

        // 7. DEVICE BAN CHECK
        try {
            if ($this->isCurrentDeviceBanned()) {
                http_response_code(403);
                echo json_encode(["message" => "This device is banned from the platform."]);
                exit();
            }
        } catch (Exception $e) {
            error_log("Device ban check failed: " . $e->getMessage());
        }

        return $user_id;
    }

    public static function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        if (strpos($ip, ',') !== false) {
            $parts = explode(',', $ip);
            $ip = trim((string)$parts[0]);
        }
        return (string)$ip;
    }

    public static function getClientFingerprint(): string
    {
        $raw = (string)($_SERVER['HTTP_X_DEVICE_FINGERPRINT'] ?? '');
        if ($raw !== '') {
            return substr(hash('sha256', $raw), 0, 120);
        }
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        $ip = self::getClientIp();
        return substr(hash('sha256', $ua . '|' . $ip), 0, 120);
    }

    public function isCurrentDeviceBanned(): bool
    {
        $fp = self::getClientFingerprint();
        $ip = self::getClientIp();
        $query = "
            SELECT 1
            FROM device_bans
            WHERE device_fingerprint = :fp
               OR ip_address = :ip
            LIMIT 1
        ";
        if (db_is_pgsql($this->db)) {
            $query = "
                SELECT 1
                FROM device_bans
                WHERE device_fingerprint = :fp
                   OR ip_address = CAST(:ip AS inet)
                LIMIT 1
            ";
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute(['fp' => $fp, 'ip' => $ip]);
        return (bool)$stmt->fetchColumn();
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
