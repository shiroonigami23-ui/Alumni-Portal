<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_profile_media.php';
require_once __DIR__ . '/_privacy.php';

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
            COALESCE(p.show_email, TRUE) AS show_email,
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

    $viewerRole = privacy_role($db, $currentUserId);
    if ($currentUserId !== $userId && $viewerRole !== 'admin' && privacy_is_blocked($db, $currentUserId, $userId)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    // Chat headers need name/photo/role even for private accounts; email only if the owner shares it.
    $showEmail = $currentUserId === $userId || $viewerRole === 'admin'
        || (filter_var($row['show_email'], FILTER_VALIDATE_BOOLEAN) && privacy_can_view($db, $currentUserId, $viewerRole, $userId));

    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => (int)$row['user_id'],
            'full_name' => (string)$row['full_name'],
            'email' => $showEmail ? (string)$row['email'] : null,
            'role' => (string)$row['role'],
            'branch' => $row['branch'] ?? null,
            'profile_picture_url' => resolve_profile_media_url($db, (int)$row['user_id'], $row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null, 'profile_picture_url', 'profile_avatar')
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch user'
    ]);
}
