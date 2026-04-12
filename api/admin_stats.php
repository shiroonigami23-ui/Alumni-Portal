<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../config/DbCompat.php';
include_once __DIR__ . '/_community_schema.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    respond(["success" => false, "message" => "Database unavailable."], 503);
}
$auth = new Auth($db);
ensure_community_schema($db);

try {
    $user_id = $auth->validateRequest();
    $role_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $role_stmt->execute(['uid' => $user_id]);
    $role = (string)$role_stmt->fetchColumn();
    if ($role !== 'admin') {
        respond(["success" => false, "message" => "Unauthorized. Admin access only."], 403);
    }

    $type = strtolower((string)($_GET['type'] ?? 'summary'));

    if ($type === 'pending') {
        $emailNameExpr = db_email_local_part_expr($db, 'u.email');
        $query = "
            SELECT
                u.user_id,
                u.email,
                u.role,
                u.status,
                u.created_at,
                COALESCE(p.full_name, {$emailNameExpr}) AS full_name,
                p.branch,
                p.graduation_year,
                p.profile_picture_url
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE u.status = 'pending'
            ORDER BY u.created_at ASC
            LIMIT 200
        ";
        $stmt = $db->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array_map(static function (array $row): array {
            return [
                'id' => (int)$row['user_id'],
                'name' => (string)$row['full_name'],
                'email' => (string)$row['email'],
                'role' => (string)$row['role'],
                'status' => (string)$row['status'],
                'created_at' => $row['created_at'],
                'branch' => $row['branch'] ?? null,
                'graduation_year' => $row['graduation_year'] ? (int)$row['graduation_year'] : null,
                'avatar' => !empty($row['profile_picture_url']) ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null
            ];
        }, $rows);
        respond([
            "success" => true,
            "status" => "success",
            "count" => count($data),
            "data" => $data
        ]);
    }

    if ($type === 'activity') {
        $query = "
            SELECT
                l.log_id,
                l.action,
                l.details,
                l.severity,
                l.created_at,
                COALESCE(u.email, 'system') AS actor_email
            FROM activity_logs l
            LEFT JOIN users u ON u.user_id = l.user_id
            ORDER BY l.created_at DESC
            LIMIT 20
        ";
        $stmt = $db->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array_map(static function (array $row): array {
            return [
                'id' => (int)$row['log_id'],
                'action' => (string)($row['action'] ?? 'ADMIN_ACTION'),
                'details' => (string)($row['details'] ?? ''),
                'severity' => (string)($row['severity'] ?? 'INFO'),
                'created_at' => $row['created_at'],
                'actor' => (string)($row['actor_email'] ?? 'system')
            ];
        }, $rows);
        respond([
            "success" => true,
            "status" => "success",
            "count" => count($data),
            "data" => $data
        ]);
    }

    $total_users = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $pending_users = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $active_reports = (int)$db->query("SELECT COUNT(*) FROM reports WHERE reviewed_at IS NULL")->fetchColumn();
    $new_users_today = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= CURRENT_DATE")->fetchColumn();
    if (db_is_mysql($db)) {
        $new_users_month = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')")->fetchColumn();
        $prev_month_users = (int)$db->query("
            SELECT COUNT(*)
            FROM users
            WHERE created_at >= DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH), '%Y-%m-01')
              AND created_at < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
        ")->fetchColumn();
    } else {
        $new_users_month = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= date_trunc('month', CURRENT_DATE)")->fetchColumn();
        $prev_month_users = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= date_trunc('month', CURRENT_DATE - interval '1 month') AND created_at < date_trunc('month', CURRENT_DATE)")->fetchColumn();
    }
    $user_growth = $prev_month_users > 0 ? round((($new_users_month - $prev_month_users) / $prev_month_users) * 100, 1) : ($new_users_month > 0 ? 100 : 0);

    if (db_is_mysql($db)) {
        $db_size_mb = (float)$db->query("
            SELECT COALESCE(SUM(data_length + index_length), 0) / 1024.0 / 1024.0
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ")->fetchColumn();
    } else {
        $db_size_mb = (float)$db->query("SELECT pg_database_size(current_database()) / 1024.0 / 1024.0")->fetchColumn();
    }
    $storage_usage = (int)max(1, min(99, round(($db_size_mb / 1024) * 100)));
    $system_health = 100;
    if ($active_reports > 25) {
        $system_health = 88;
    } elseif ($active_reports > 10) {
        $system_health = 94;
    }

    $stats = [
        "total_users" => $total_users,
        "pending_users" => $pending_users,
        "active_reports" => $active_reports,
        "new_users_today" => $new_users_today,
        "new_users_month" => $new_users_month,
        "user_growth" => $user_growth,
        "system_health" => $system_health,
        "storage_usage" => $storage_usage
    ];

    respond([
        "success" => true,
        "status" => "success",
        "data" => $stats,
        // legacy
        "total_users" => $stats["total_users"],
        "pending_users" => $stats["pending_users"],
        "active_reports" => $stats["active_reports"]
    ]);
} catch (Throwable $e) {
    respond(["success" => false, "message" => "Failed to load admin stats.", "error" => $e->getMessage()], 500);
}
