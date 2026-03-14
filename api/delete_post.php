<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_content_store.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;

    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id is required."]);
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
        echo json_encode(["success" => false, "status" => "error", "message" => "You can delete only your own post."]);
        exit;
    }

    $db->prepare("DELETE FROM posts WHERE post_id = :pid")->execute([':pid' => $post_id]);

    delete_content_payload($db, (string)$post['content_file_path']);

    echo json_encode(["success" => true, "status" => "success", "message" => "Post deleted."]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}
