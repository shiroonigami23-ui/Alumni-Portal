<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

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

    $stmt = $db->prepare("
        SELECT c.comment_id, c.post_id, c.content_file_path, c.created_at
        FROM comments c
        JOIN posts p ON p.post_id = c.post_id
        WHERE c.user_id = :uid
          AND p.status = 'published'
        ORDER BY c.created_at DESC
        LIMIT 200
    ");
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $content = '';
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$row['content_file_path']);
        if (is_file($path)) {
            $raw = file_get_contents($path);
            $decoded = json_decode((string)$raw, true);
            if (is_array($decoded) && array_key_exists('content', $decoded)) {
                $content = (string)($decoded['content'] ?? '');
            } else {
                $content = (string)$raw;
            }
        }
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
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

