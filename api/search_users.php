<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $currentUserId = (int)$auth->validateRequest();

    $q = trim((string)($_GET['q'] ?? ''));
    $search = '%' . mb_strtolower($q) . '%';

    $sql = "
        SELECT
            u.user_id,
            u.role,
            u.email,
            COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name,
            p.branch,
            p.profile_picture_url
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.user_id
        WHERE u.user_id <> :me
          AND u.status = 'active'
          AND (
                :q = ''
                OR LOWER(COALESCE(p.full_name, '')) LIKE :search
                OR LOWER(u.email) LIKE :search
                OR LOWER(COALESCE(p.branch, '')) LIKE :search
              )
        ORDER BY
            CASE WHEN LOWER(COALESCE(p.full_name, '')) LIKE :prefix THEN 0 ELSE 1 END,
            COALESCE(p.full_name, u.email) ASC
        LIMIT 30
    ";

    $prefix = mb_strtolower($q) . '%';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':me' => $currentUserId,
        ':q' => $q,
        ':search' => $search,
        ':prefix' => $prefix
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array_map(static function (array $row): array {
        return [
            'user_id' => (int)$row['user_id'],
            'full_name' => (string)$row['full_name'],
            'email' => (string)$row['email'],
            'role' => (string)$row['role'],
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
        'error' => $e->getMessage()
    ]);
}

