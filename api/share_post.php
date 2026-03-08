<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_feed_schema.php';

$database = new Database();
$db = $database->getConnection();
ensure_feed_metrics_schema($db);
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;

    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id is required"]);
        exit;
    }

    $stmt = $db->prepare("SELECT COALESCE(share_count, 0) FROM posts WHERE post_id = :pid");
    $stmt->execute(['pid' => $post_id]);
    $count = $stmt->fetchColumn();
    if ($count === false) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Post not found"]);
        exit;
    }
    $count = (int)$count;

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Use create_share_link.php and track_share_open.php for share counting.",
        "shares_count" => $count
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}
