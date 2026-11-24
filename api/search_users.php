<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_moderation_schema.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $currentUserId = (int)$auth->validateRequest();
    $currentUserRole = moderation_get_user_role($db, $currentUserId);

    $q = trim((string)($_GET['q'] ?? ''));
    $search = '%' . mb_strtolower($q) . '%';

    $sql = "
        SELECT
            u.user_id,
            u.role,
            u.email,
            u.username,
            COALESCE(p.show_email, TRUE) AS show_email,
            COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name,
            p.branch,
            p.profile_picture_url
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.user_id
        WHERE u.user_id <> :me
          AND u.status = 'active'
          AND (:viewer_is_admin = 1 OR u.role <> 'admin')
          AND (
                :q = ''
                OR LOWER(COALESCE(p.full_name, '')) LIKE :search
                OR LOWER(COALESCE(u.username, '')) LIKE :handle
                OR (:viewer_is_admin = 1 AND LOWER(u.email) LIKE :search)
                OR LOWER(COALESCE(p.branch, '')) LIKE :search
              )
          AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.blocker_user_id = :me AND b.blocked_user_id = u.user_id)
                                                  OR (b.blocked_user_id = :me AND b.blocker_user_id = u.user_id))
        ORDER BY
            CASE WHEN LOWER(COALESCE(p.full_name, '')) LIKE :prefix THEN 0 ELSE 1 END,
            COALESCE(p.full_name, u.email) ASC
        LIMIT 30
    ";

    $prefix = mb_strtolower($q) . '%';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':me' => $currentUserId,
        ':viewer_is_admin' => $currentUserRole === 'admin' ? 1 : 0,
        ':q' => $q,
        ':search' => $search,
        ':handle' => ltrim(mb_strtolower($q), '@') . '%',
        ':prefix' => $prefix
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $isAdmin = $currentUserRole === 'admin';
    $data = array_map(static function (array $row) use ($isAdmin): array {
        if (!$isAdmin && !filter_var($row['show_email'], FILTER_VALIDATE_BOOLEAN)) {
            $row['email'] = '';
        }
        return [
            'user_id' => (int)$row['user_id'],
            'full_name' => (string)$row['full_name'],
            'email' => (string)$row['email'],
            'role' => (string)$row['role'],
            'username' => $row['username'] ?? null,
            'branch' => $row['branch'] ?? null,
            'profile_picture_url' => $row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to search users',
        'error_ref' => api_error_ref($e)
    ]);
}
