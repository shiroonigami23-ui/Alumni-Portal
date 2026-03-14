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
    $roleStmt = $db->prepare("SELECT LOWER(role) FROM users WHERE user_id = :uid LIMIT 1");
    $roleStmt->execute([':uid' => $user_id]);
    $currentRole = (string)($roleStmt->fetchColumn() ?: '');
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $comment_id = isset($data['comment_id']) ? (int)$data['comment_id'] : 0;

    if ($comment_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "comment_id is required."]);
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

    $canDelete = ((int)$comment['user_id'] === (int)$user_id) || ($currentRole === 'admin');
    if (!$canDelete) {
        http_response_code(403);
        echo json_encode(["success" => false, "status" => "error", "message" => "You do not have permission to delete this comment."]);
        exit;
    }

    $db->prepare("DELETE FROM comments WHERE comment_id = :cid")->execute([':cid' => $comment_id]);

    delete_content_payload($db, (string)$comment['content_file_path']);

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Comment deleted.",
        "data" => ["comment_id" => $comment_id, "post_id" => (int)$comment['post_id']]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}
