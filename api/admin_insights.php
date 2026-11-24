<?php
/**
 * Admin Insights — one call for the admin control centre.
 *
 * GET                       growth, breakdowns, engagement, security events, audit log, health
 * POST {action:set_banner, message, tone: info|success|warning|danger, link?, expires_at?}
 * POST {action:clear_banner}
 * POST {action:broadcast, audience: all|student|alumni|faculty, message}
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'admin']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_portal_settings.php';
include_once __DIR__ . '/../helpers/Cache.php';

$t0 = microtime(true);
$db = (new Database())->getConnection();
$auth = new Auth($db);
$adminId = (int)$auth->validateRequest();
$dbMs = round((microtime(true) - $t0) * 1000, 1);

$r = $db->prepare("SELECT role FROM users WHERE user_id = :u");
$r->execute(['u' => $adminId]);
if ((string)$r->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admins only.']);
    exit;
}

function ins_count(PDO $db, string $sql, array $p = []): ?int
{
    try {
        $s = $db->prepare($sql);
        $s->execute($p);
        return (int)$s->fetchColumn();
    } catch (Throwable $e) {
        return null; // table may not exist on this install
    }
}

function ins_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $body = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
    $action = (string)($body['action'] ?? '');

    if ($action === 'set_banner') {
        $message = trim(mb_substr(strip_tags((string)($body['message'] ?? '')), 0, 280));
        if (mb_strlen($message) < 3) {
            ins_out(['success' => false, 'message' => 'Banner message is too short.'], 400);
        }
        $tone = in_array($body['tone'] ?? '', ['info', 'success', 'warning', 'danger'], true) ? $body['tone'] : 'info';
        $link = trim((string)($body['link'] ?? ''));
        if ($link !== '' && !preg_match('#^(https://|/|[a-z0-9_-]+\.php)#i', $link)) {
            ins_out(['success' => false, 'message' => 'Link must be https://… or a portal page like events.php'], 400);
        }
        $expires = (string)($body['expires_at'] ?? '');
        $banner = [
            'id' => bin2hex(random_bytes(4)),
            'message' => $message,
            'tone' => $tone,
            'link' => $link ?: null,
            'link_text' => trim(mb_substr((string)($body['link_text'] ?? ''), 0, 40)) ?: 'Learn more',
            'expires_at' => preg_match('/^\d{4}-\d{2}-\d{2}/', $expires) ? $expires : null,
            'updated_at' => gmdate('c'),
        ];
        portal_setting_set($db, 'site_banner', $banner, $adminId);
        Cache::forget('site_banner');
        $auth->logAction($adminId, 'SITE_BANNER_SET', $message);
        ins_out(['success' => true, 'message' => 'Banner is live on every page.', 'data' => $banner]);
    }

    if ($action === 'clear_banner') {
        portal_setting_set($db, 'site_banner', null, $adminId);
        Cache::forget('site_banner');
        $auth->logAction($adminId, 'SITE_BANNER_CLEAR', '');
        ins_out(['success' => true, 'message' => 'Banner removed.']);
    }

    if ($action === 'broadcast') {
        $message = trim(mb_substr(strip_tags((string)($body['message'] ?? '')), 0, 500));
        $audience = (string)($body['audience'] ?? 'all');
        if (mb_strlen($message) < 5) {
            ins_out(['success' => false, 'message' => 'Write a message of at least 5 characters.'], 400);
        }
        if (!in_array($audience, ['all', 'student', 'alumni', 'faculty'], true)) {
            ins_out(['success' => false, 'message' => 'Unknown audience.'], 400);
        }
        $where = "u.status = 'active' AND u.user_id <> :admin" . ($audience !== 'all' ? ' AND u.role = :role' : '');
        $params = ['admin' => $adminId, 'msg' => '📣 ' . $message, 'from' => $adminId];
        if ($audience !== 'all') {
            $params['role'] = $audience;
        }
        $sent = 0;
        foreach (['announcement', 'general'] as $type) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO notifications (user_id, notification_type, related_user_id, content)
                    SELECT u.user_id, '$type', :from, :msg FROM users u WHERE $where
                ");
                $stmt->execute($params);
                $sent = $stmt->rowCount();
                break;
            } catch (Throwable $e) {
                // retry with a generic notification type
            }
        }
        $auth->logAction($adminId, 'BROADCAST', "[$audience] $message");
        ins_out(['success' => true, 'message' => "Sent to $sent " . ($audience === 'all' ? 'members' : $audience . ($audience === 'alumni' ? '' : 's')) . '.', 'data' => ['sent' => $sent]]);
    }

    ins_out(['success' => false, 'message' => 'Unknown action.'], 404);
}

try {
    // ---- Totals --------------------------------------------------------------
    $totals = [
        'users' => ins_count($db, "SELECT COUNT(*) FROM users"),
        'active' => ins_count($db, "SELECT COUNT(*) FROM users WHERE status = 'active'"),
        'pending' => ins_count($db, "SELECT COUNT(*) FROM users WHERE status IN ('pending','pending_verification')"),
        'banned' => ins_count($db, "SELECT COUNT(*) FROM users WHERE status IN ('banned','suspended')"),
        'posts' => ins_count($db, "SELECT COUNT(*) FROM posts"),
        'open_reports' => ins_count($db, "SELECT COUNT(*) FROM reports WHERE status = 'pending'"),
        'referral_offers' => ins_count($db, "SELECT COUNT(*) FROM referral_offers WHERE status = 'open'"),
        'referrals_made' => ins_count($db, "SELECT COUNT(*) FROM referral_requests WHERE status = 'referred'"),
        'mentorship_active' => ins_count($db, "SELECT COUNT(*) FROM mentorship_matches WHERE status = 'active'"),
        'jobs' => ins_count($db, "SELECT COUNT(*) FROM jobs"),
        'events' => ins_count($db, "SELECT COUNT(*) FROM events"),
    ];

    // ---- Breakdowns ------------------------------------------------------------
    $roles = $db->query("SELECT role, COUNT(*) AS n FROM users GROUP BY role ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);
    $statuses = $db->query("SELECT status, COUNT(*) AS n FROM users GROUP BY status ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);

    // ---- Growth: sign-ups per week, last 12 weeks (bucketed in PHP for pgsql/mysql parity)
    $since = date('Y-m-d H:i:s', strtotime('-12 weeks'));
    $s = $db->prepare("SELECT created_at, role FROM users WHERE created_at >= :s");
    $s->execute(['s' => $since]);
    $weeks = [];
    for ($i = 11; $i >= 0; $i--) {
        $weeks[date('o-\WW', strtotime("-$i weeks"))] = ['week' => date('d M', strtotime("monday this week -$i weeks")), 'total' => 0, 'student' => 0, 'alumni' => 0, 'faculty' => 0];
    }
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $k = date('o-\WW', strtotime((string)$row['created_at']));
        if (isset($weeks[$k])) {
            $weeks[$k]['total']++;
            if (isset($weeks[$k][$row['role']])) {
                $weeks[$k][$row['role']]++;
            }
        }
    }

    // ---- Engagement (last 7 days) -------------------------------------------
    $week = date('Y-m-d H:i:s', strtotime('-7 days'));
    $engagement = [
        'new_users_7d' => ins_count($db, "SELECT COUNT(*) FROM users WHERE created_at >= :w", ['w' => $week]),
        'posts_7d' => ins_count($db, "SELECT COUNT(*) FROM posts WHERE created_at >= :w", ['w' => $week]),
        'comments_7d' => ins_count($db, "SELECT COUNT(*) FROM comments WHERE created_at >= :w", ['w' => $week]),
        'logins_7d' => ins_count($db, "SELECT COUNT(*) FROM users WHERE last_login >= :w", ['w' => $week]),
    ];

    // ---- Top companies --------------------------------------------------------
    $companies = [];
    try {
        $companies = $db->query("
            SELECT p.current_company AS company, COUNT(*) AS n
            FROM profiles p JOIN users u ON u.user_id = p.user_id
            WHERE u.role = 'alumni' AND p.current_company IS NOT NULL AND p.current_company <> ''
            GROUP BY p.current_company ORDER BY n DESC LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    }

    // ---- Security events (tail of the log) ------------------------------------
    $events = [];
    $counts = [];
    $logFile = dirname(__DIR__) . '/storage/logs/security.log';
    if (is_file($logFile)) {
        $fh = fopen($logFile, 'r');
        $size = filesize($logFile);
        fseek($fh, max(0, $size - 256 * 1024));
        $lines = array_filter(explode("\n", (string)stream_get_contents($fh)));
        fclose($fh);
        $dayAgo = time() - 86400;
        foreach (array_reverse($lines) as $line) {
            $e = json_decode($line, true);
            if (!is_array($e)) {
                continue;
            }
            if (strtotime($e['t'] ?? '') >= $dayAgo) {
                $counts[$e['type']] = ($counts[$e['type']] ?? 0) + 1;
            }
            if (count($events) < 60) {
                unset($e['ua']);
                $events[] = $e;
            }
        }
    }

    // ---- Audit log --------------------------------------------------------------
    $audit = [];
    try {
        $audit = $db->query("
            SELECT l.action, l.details, l.ip_address, l.created_at, COALESCE(p.full_name, u.email) AS actor
            FROM activity_logs l
            LEFT JOIN users u ON u.user_id = l.user_id
            LEFT JOIN profiles p ON p.user_id = l.user_id
            ORDER BY l.created_at DESC LIMIT 40
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    }

    // ---- Health ---------------------------------------------------------------
    $root = dirname(__DIR__);
    $free = @disk_free_space($root);
    $total = @disk_total_space($root);
    $health = [
        'db_driver' => db_driver($db),
        'db_latency_ms' => $dbMs,
        'php_version' => PHP_VERSION,
        'app_env' => getenv('APP_ENV') ?: 'local',
        'debug' => getenv('APP_DEBUG') === 'true',
        'https' => function_exists('ap_is_https') ? ap_is_https() : false,
        'storage_writable' => is_writable($root . '/storage'),
        'disk_free_gb' => $free ? round($free / 1073741824, 1) : null,
        'disk_used_pct' => ($free && $total) ? round(100 - $free / $total * 100) : null,
        'rate_limited_clients' => is_dir($root . '/storage/cache/ratelimit') ? count(glob($root . '/storage/cache/ratelimit/*.json') ?: []) : 0,
        'server_time' => gmdate('c'),
    ];

    ins_out(['success' => true, 'data' => [
        'totals' => $totals,
        'roles' => $roles,
        'statuses' => $statuses,
        'growth' => array_values($weeks),
        'engagement' => $engagement,
        'companies' => $companies,
        'security' => ['last_24h' => $counts, 'events' => $events],
        'audit' => $audit,
        'health' => $health,
        'banner' => portal_setting_get($db, 'site_banner'),
    ]]);
} catch (Throwable $e) {
    error_log('admin_insights: ' . $e->getMessage());
    ins_out(['success' => false, 'message' => 'Could not load insights right now.'], 500);
}
