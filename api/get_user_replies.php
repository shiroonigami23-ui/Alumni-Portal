<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_content_store.php';
require_once __DIR__ . '/_privacy.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $viewerId = (int)$auth->validateRequest();
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "user_id is required"]);
        exit;
    }

    $viewerRole = privacy_role($db, $viewerId);
    if (!privacy_can_view($db, $viewerId, $viewerRole, $userId)) {
        echo json_encode(["success" => true, "status" => "success", "data" => [], "locked" => true]);
        exit;
    }
    // Only replies on posts this viewer is allowed to see.
    $stmt = $db->prepare("
        SELECT c.comment_id, c.post_id, c.content_file_path, c.created_at
        FROM comments c
        JOIN posts p ON p.post_id = c.post_id
        WHERE c.user_id = :uid
          AND " . privacy_post_visible_sql('p') . "
        ORDER BY c.created_at DESC
        LIMIT 200
    ");
    $stmt->execute([':uid' => $userId, ':viewer_uid' => $viewerId, ':viewer_role' => $viewerRole]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $payload = load_content_payload($db, (string)$row['content_file_path']);
        $content = $payload['content'];
        $items[] = [
            'comment_id' => (int)$row['comment_id'],
            'post_id' => (int)$row['post_id'],
            'content' => $content,
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        "success" => true,
        "status" => "success",
        "data" => $items
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('get_user_replies: ' . $e->getMessage());
    echo json_encode(["success" => false, "status" => "error", "message" => "Could not load replies."]);
}
