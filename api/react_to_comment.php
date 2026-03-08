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
    $comment_id = isset($data['comment_id']) ? (int)$data['comment_id'] : 0;
    $reaction = trim((string)($data['reaction'] ?? 'like'));
    if ($reaction === '') {
        $reaction = 'like';
    }

    if ($comment_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "comment_id required."]);
        exit;
    }

    $exists = $db->prepare("SELECT comment_id FROM comments WHERE comment_id = :cid");
    $exists->execute([':cid' => $comment_id]);
    if (!$exists->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Comment not found."]);
        exit;
    }

    $db->beginTransaction();
    $check = $db->prepare("SELECT comment_reaction_id FROM comment_reactions WHERE comment_id = :cid AND user_id = :uid LIMIT 1");
    $check->execute([':cid' => $comment_id, ':uid' => $user_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    $liked = false;
    if ($existing) {
        $db->prepare("DELETE FROM comment_reactions WHERE comment_id = :cid AND user_id = :uid")
           ->execute([':cid' => $comment_id, ':uid' => $user_id]);
        $db->prepare("UPDATE comments SET reaction_count = GREATEST(reaction_count - 1, 0), updated_at = NOW() WHERE comment_id = :cid")
           ->execute([':cid' => $comment_id]);
        $liked = false;
    } else {
        $ins = $db->prepare("INSERT INTO comment_reactions (comment_id, user_id, reaction_type) VALUES (:cid, :uid, :rtype)");
        $ins->execute([':cid' => $comment_id, ':uid' => $user_id, ':rtype' => $reaction]);
        $db->prepare("UPDATE comments SET reaction_count = reaction_count + 1, updated_at = NOW() WHERE comment_id = :cid")
           ->execute([':cid' => $comment_id]);
        $liked = true;
    }

    $cntStmt = $db->prepare("SELECT reaction_count FROM comments WHERE comment_id = :cid");
    $cntStmt->execute([':cid' => $comment_id]);
    $count = (int)$cntStmt->fetchColumn();
    $db->commit();

    echo json_encode([
        "success" => true,
        "status" => "success",
        "liked" => $liked,
        "likes_count" => $count,
        "message" => $liked ? "Comment liked." : "Like removed."
    ]);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

