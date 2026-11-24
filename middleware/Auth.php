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

    /** Per-request cache: the API kernel and older in-file checks share one lookup. */
    private static $memo = [];

    /** Bearer token from the request (Authorization header on any server type), or null. */
    public static function bearerToken(): ?string
    {
        $authHeader = null;
        if (function_exists('apache_request_headers')) {
            foreach ((array)apache_request_headers() as $k => $v) {
                if (strtolower((string)$k) === 'authorization') {
                    $authHeader = (string)$v;
                    break;
                }
            }
        }
        // nginx / PHP-FPM / CGI expose it here instead
        $authHeader = $authHeader ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null);
        if ($authHeader && preg_match('/Bearer\s+(\S+)/i', (string)$authHeader, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Check the caller without exiting.
     * Returns ['ok' => bool, 'code' => int, 'message' => string, 'user_id' => int|null, ...extra].
     */
    public function authenticate(): array
    {
        if (!$this->db) {
            return ['ok' => false, 'code' => 503, 'message' => 'Database unavailable.', 'user_id' => null];
        }
        $token = self::bearerToken();
        if (!$token) {
            return ['ok' => false, 'code' => 401, 'message' => 'Unauthorized. No token provided.', 'user_id' => null];
        }
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $memoKey = hash('sha256', $token) . '|' . $method;
        if (isset(self::$memo[$memoKey])) {
            return self::$memo[$memoKey];
        }

        $user_session = $this->session->isValid($token);
        if (!$user_session) {
            return self::$memo[$memoKey] = ['ok' => false, 'code' => 401, 'message' => 'Unauthorized. Invalid or expired token.', 'user_id' => null];
        }
        $user_id = (int)$user_session['user_id'];

        // Permanent ban & suspension (Section 6)
        $u_stmt = $this->db->prepare("SELECT status, suspension_expires_at FROM users WHERE user_id = :uid");
        $u_stmt->execute(['uid' => $user_id]);
        $user = $u_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return self::$memo[$memoKey] = ['ok' => false, 'code' => 401, 'message' => 'Unauthorized. Account not found.', 'user_id' => null];
        }
        if ($user['status'] === 'banned') {
            return self::$memo[$memoKey] = ['ok' => false, 'code' => 403, 'message' => 'Account is permanently banned.', 'user_id' => null];
        }
        if ($user['status'] === 'suspended' && strtotime((string)$user['suspension_expires_at']) > time()) {
            return self::$memo[$memoKey] = ['ok' => false, 'code' => 403, 'message' => 'Account is suspended until ' . $user['suspension_expires_at'], 'user_id' => null];
        }

        // Shadow ban: reported abuse threshold
        try {
            $s_stmt = $this->db->prepare("SELECT shadow_ban_until FROM moderation_strikes WHERE user_id = :uid");
            $s_stmt->execute(['uid' => $user_id]);
            $shadow_ban = $s_stmt->fetchColumn();
            if ($shadow_ban && strtotime((string)$shadow_ban) > time()) {
                $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
                $readBlockedScripts = ['get_feed.php', 'public_feed.php', 'search_posts.php', 'get_user_replies.php'];
                if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) || ($method === 'GET' && in_array($script, $readBlockedScripts, true))) {
                    return self::$memo[$memoKey] = ['ok' => false, 'code' => 403, 'message' => "Your account is shadow banned until $shadow_ban.",
                                                     'user_id' => null, 'is_shadow_banned' => true];
                }
            }
        } catch (Throwable $e) {
            error_log("Moderation strikes check failed: " . $e->getMessage());
        }

        // Device ban
        try {
            if ($this->isCurrentDeviceBanned()) {
                return self::$memo[$memoKey] = ['ok' => false, 'code' => 403, 'message' => 'This device is banned from the platform.', 'user_id' => null];
            }
        } catch (Throwable $e) {
            error_log("Device ban check failed: " . $e->getMessage());
        }

        return self::$memo[$memoKey] = ['ok' => true, 'code' => 200, 'message' => '', 'user_id' => $user_id];
    }

    /** Old-style check: returns the user id or ends the request with 401/403. */
    public function validateRequest() {
        $r = $this->authenticate();
        if (!$r['ok']) {
            http_response_code($r['code']);
            $body = ['success' => false, 'message' => $r['message']];
            if (!empty($r['is_shadow_banned'])) {
                $body['is_shadow_banned'] = true;
            }
            echo json_encode($body);
            exit();
        }
        return $r['user_id'];
    }

    public static function getClientIp(): string
    {
        // Security layer resolves X-Forwarded-For only from trusted proxies (anti-spoofing)
        if (function_exists('ap_client_ip')) {
            return ap_client_ip();
        }
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return $ip;
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
        $ip = self::getClientIp();
        $stmt->execute(['uid' => $user_id, 'action' => $action, 'details' => $details, 'ip' => $ip]);

        $log_entry = "[" . date('Y-m-d H:i:s') . "] User ID: $user_id | Action: $action | Details: $details | IP: $ip" . PHP_EOL;
        $log_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'activity.log';
        if (!file_exists(dirname($log_file))) { mkdir(dirname($log_file), 0750, true); }
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
