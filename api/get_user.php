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

    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT
            u.user_id,
            u.email,
            u.role,
            p.branch,
            p.is_private,
            COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name,
            p.profile_picture_url
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.user_id
        WHERE u.user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $isPrivate = (bool)($row['is_private'] ?? false);
    if ($isPrivate && $currentUserId !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This profile is private']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => (int)$row['user_id'],
            'full_name' => (string)$row['full_name'],
            'email' => (string)$row['email'],
            'role' => (string)$row['role'],
            'branch' => $row['branch'] ?? null,
            'profile_picture_url' => $row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch user',
        'error' => $e->getMessage()
    ]);
}

