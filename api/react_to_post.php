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

    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id is required"]);
        exit;
    }

    $db->beginTransaction();

    // Optimistic toggle with conflict-safe insert first.
    $ins = $db->prepare("
        INSERT INTO likes (user_id, post_id)
        VALUES (:uid, :pid)
        ON CONFLICT (user_id, post_id) DO NOTHING
    ");
    $ins->execute(['uid' => $user_id, 'pid' => $post_id]);

    if ($ins->rowCount() > 0) {
        $db->prepare("UPDATE posts SET reaction_count = reaction_count + 1 WHERE post_id = :pid")
           ->execute(['pid' => $post_id]);
        $liked = true;
    } else {
        $del = $db->prepare("DELETE FROM likes WHERE user_id = :uid AND post_id = :pid");
        $del->execute(['uid' => $user_id, 'pid' => $post_id]);
        if ($del->rowCount() > 0) {
            $db->prepare("UPDATE posts SET reaction_count = GREATEST(0, reaction_count - 1) WHERE post_id = :pid")
               ->execute(['pid' => $post_id]);
        }
        $liked = false;
    }

    $countStmt = $db->prepare("SELECT reaction_count FROM posts WHERE post_id = :pid");
    $countStmt->execute(['pid' => $post_id]);
    $count = (int)$countStmt->fetchColumn();

    $db->commit();
    echo json_encode([
        "success" => true,
        "status" => "success",
        "liked" => $liked,
        "likes_count" => $count,
        "message" => $liked ? "Post liked." : "Like removed."
    ]);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}
