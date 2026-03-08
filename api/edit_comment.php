<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $user_id = (int)$auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $comment_id = isset($data['comment_id']) ? (int)$data['comment_id'] : 0;
    $content = trim((string)($data['content'] ?? ''));

    if ($comment_id <= 0 || $content === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "comment_id and content are required."]);
        exit;
    }

    $stmt = $db->prepare("SELECT user_id, content_file_path, post_id FROM comments WHERE comment_id = :cid LIMIT 1");
    $stmt->execute([':cid' => $comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$comment) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Comment not found."]);
        exit;
    }
    if ((int)$comment['user_id'] !== $user_id) {
        http_response_code(403);
        echo json_encode(["success" => false, "status" => "error", "message" => "You can edit only your own comment."]);
        exit;
    }

    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$comment['content_file_path']);
    $attachments = [];
    if (is_file($abs)) {
        $raw = file_get_contents($abs);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            $attachments = is_array($decoded['attachments'] ?? null) ? $decoded['attachments'] : [];
        }
    }
    $payload = [
        'content' => $content,
        'attachments' => $attachments
    ];
    file_put_contents($abs, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $db->prepare("UPDATE comments SET is_edited = true, updated_at = NOW() WHERE comment_id = :cid")
       ->execute([':cid' => $comment_id]);

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Comment updated.",
        "data" => [
            "comment_id" => $comment_id,
            "post_id" => (int)$comment['post_id'],
            "content" => $content
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

