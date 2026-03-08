<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $roleStmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $roleStmt->execute(['uid' => $user_id]);
    if ($roleStmt->fetchColumn() !== 'admin') {
        respond(["success" => false, "message" => "Admin access only."], 403);
    }

    $search = trim((string)($_GET['search'] ?? ''));
    $role = trim((string)($_GET['role'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;

    $where = " WHERE 1=1 ";
    $params = [];

    if ($search !== '') {
        $where .= " AND (LOWER(u.email) LIKE LOWER(:search) OR LOWER(COALESCE(p.full_name, '')) LIKE LOWER(:search)) ";
        $params[':search'] = '%' . $search . '%';
    }
    if ($role !== '') {
        $where .= " AND u.role = :role ";
        $params[':role'] = $role;
    }
    if ($status !== '') {
        $where .= " AND u.status = :status ";
        $params[':status'] = $status;
    }

    $countStmt = $db->prepare("
        SELECT COUNT(*)
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.user_id
        $where
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $query = "
        SELECT
            u.user_id,
            u.email,
            u.role,
            u.status,
            u.created_at,
            u.last_login,
            COALESCE(p.full_name, split_part(u.email, '@', 1)) AS full_name,
            p.branch,
            p.graduation_year,
            p.profile_picture_url
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.user_id
        $where
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_map(static function (array $row): array {
        return [
            'id' => (int)$row['user_id'],
            'name' => (string)$row['full_name'],
            'email' => (string)$row['email'],
            'role' => (string)$row['role'],
            'status' => (string)$row['status'],
            'created_at' => $row['created_at'],
            'last_login' => $row['last_login'],
            'branch' => $row['branch'] ?? null,
            'graduation_year' => $row['graduation_year'] ? (int)$row['graduation_year'] : null,
            'avatar' => !empty($row['profile_picture_url']) ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null
        ];
    }, $rows);

    respond([
        "success" => true,
        "status" => "success",
        "total" => $total,
        "page" => $page,
        "per_page" => $limit,
        "data" => $data
    ]);
} catch (Throwable $e) {
    respond(["success" => false, "message" => "Failed to load users.", "error" => $e->getMessage()], 500);
}

