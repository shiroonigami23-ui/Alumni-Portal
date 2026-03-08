<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_feed_schema.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_feed_metrics_schema($db);
    $auth = new Auth($db);
    $user_id = (int)$auth->validateRequest();

    $payload = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($payload['post_id']) ? (int)$payload['post_id'] : 0;
    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id is required"]);
        exit;
    }

    $db->beginTransaction();
    $ins = $db->prepare("
        INSERT INTO reposts (post_id, user_id)
        VALUES (:pid, :uid)
        ON CONFLICT (post_id, user_id) DO NOTHING
    ");
    $ins->execute([':pid' => $post_id, ':uid' => $user_id]);

    if ($ins->rowCount() > 0) {
        $db->prepare("UPDATE posts SET repost_count = COALESCE(repost_count, 0) + 1 WHERE post_id = :pid")
            ->execute([':pid' => $post_id]);
        $reposted = true;
    } else {
        $del = $db->prepare("DELETE FROM reposts WHERE post_id = :pid AND user_id = :uid");
        $del->execute([':pid' => $post_id, ':uid' => $user_id]);
        if ($del->rowCount() > 0) {
            $db->prepare("UPDATE posts SET repost_count = GREATEST(COALESCE(repost_count, 0) - 1, 0) WHERE post_id = :pid")
                ->execute([':pid' => $post_id]);
        }
        $reposted = false;
    }

    $cnt = $db->prepare("SELECT COALESCE(repost_count, 0) FROM posts WHERE post_id = :pid");
    $cnt->execute([':pid' => $post_id]);
    $count = (int)$cnt->fetchColumn();
    $db->commit();

    echo json_encode([
        "success" => true,
        "status" => "success",
        "reposted" => $reposted,
        "reposts_count" => $count
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

