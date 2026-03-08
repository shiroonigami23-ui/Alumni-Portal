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
    $user_id = $auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];

    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    $content = trim((string)($data['content'] ?? ''));

    if ($post_id <= 0 || $content === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id and content are required."]);
        exit;
    }

    $stmt = $db->prepare("SELECT user_id, content_file_path FROM posts WHERE post_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Post not found."]);
        exit;
    }
    if ((int)$post['user_id'] !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(["success" => false, "status" => "error", "message" => "You can edit only your own post."]);
        exit;
    }

    $filePath = (string)$post['content_file_path'];
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
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

    $title = mb_substr($content, 0, 80);
    $upd = $db->prepare("UPDATE posts SET title = :title, is_edited = true, last_edited_at = NOW(), updated_at = NOW() WHERE post_id = :pid");
    $upd->execute([':title' => $title, ':pid' => $post_id]);

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Post updated.",
        "data" => [
            "post_id" => $post_id,
            "content" => $content
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

