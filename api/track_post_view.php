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

    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id is required"]);
        exit;
    }

    $db->beginTransaction();

    $existsStmt = $db->prepare("SELECT post_id FROM posts WHERE post_id = :pid LIMIT 1");
    $existsStmt->execute([':pid' => $post_id]);
    if (!$existsStmt->fetchColumn()) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Post not found"]);
        exit;
    }

    $insert = $db->prepare("
        INSERT INTO post_views (post_id, user_id, viewed_at)
        VALUES (:pid, :uid, NOW())
        ON CONFLICT (post_id, user_id) DO NOTHING
    ");
    $insert->execute([':pid' => $post_id, ':uid' => $user_id]);
    $isNewView = $insert->rowCount() > 0;

    if ($isNewView) {
        $inc = $db->prepare("UPDATE posts SET view_count = COALESCE(view_count, 0) + 1 WHERE post_id = :pid");
        $inc->execute([':pid' => $post_id]);
    }

    $countStmt = $db->prepare("SELECT COALESCE(view_count, 0) FROM posts WHERE post_id = :pid");
    $countStmt->execute([':pid' => $post_id]);
    $viewCount = (int)$countStmt->fetchColumn();

    $db->commit();

    echo json_encode([
        "success" => true,
        "status" => "success",
        "post_id" => $post_id,
        "counted" => $isNewView,
        "view_count" => $viewCount
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

