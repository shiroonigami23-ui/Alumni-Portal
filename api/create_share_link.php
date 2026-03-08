<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_feed_schema.php';

function make_share_token(int $postId, int $userId): string
{
    return hash('sha256', $postId . ':' . $userId . ':' . microtime(true) . ':' . bin2hex(random_bytes(8)));
}

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_feed_metrics_schema($db);
    $auth = new Auth($db);
    $user_id = (int)$auth->validateRequest();

    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id is required"]);
        exit;
    }

    $existsStmt = $db->prepare("SELECT post_id FROM posts WHERE post_id = :pid LIMIT 1");
    $existsStmt->execute([':pid' => $post_id]);
    if (!$existsStmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Post not found"]);
        exit;
    }

    $get = $db->prepare("SELECT token FROM post_share_links WHERE post_id = :pid AND sharer_user_id = :uid LIMIT 1");
    $get->execute([':pid' => $post_id, ':uid' => $user_id]);
    $token = (string)$get->fetchColumn();

    if ($token === '') {
        $token = make_share_token($post_id, $user_id);
        $ins = $db->prepare("
            INSERT INTO post_share_links (post_id, sharer_user_id, token)
            VALUES (:pid, :uid, :token)
        ");
        $ins->execute([':pid' => $post_id, ':uid' => $user_id, ':token' => $token]);
    }

    $sharePath = "feed.php?shared_post=" . $post_id . "&share_token=" . urlencode($token) . "#post-" . $post_id;

    echo json_encode([
        "success" => true,
        "status" => "success",
        "post_id" => $post_id,
        "token" => $token,
        "share_path" => $sharePath
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

