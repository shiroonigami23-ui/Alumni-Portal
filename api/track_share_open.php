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
    $viewer_id = (int)$auth->validateRequest();

    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    $token = trim((string)($data['share_token'] ?? ''));
    if ($post_id <= 0 || $token === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id and share_token are required"]);
        exit;
    }

    $shareStmt = $db->prepare("
        SELECT share_link_id, sharer_user_id
        FROM post_share_links
        WHERE post_id = :pid AND token = :token
        LIMIT 1
    ");
    $shareStmt->execute([':pid' => $post_id, ':token' => $token]);
    $share = $shareStmt->fetch(PDO::FETCH_ASSOC);
    if (!$share) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Invalid share link"]);
        exit;
    }

    $share_link_id = (int)$share['share_link_id'];
    $sharer_user_id = (int)$share['sharer_user_id'];

    // Do not count self-open.
    if ($viewer_id === $sharer_user_id) {
        $countStmt = $db->prepare("SELECT COALESCE(share_count, 0) FROM posts WHERE post_id = :pid");
        $countStmt->execute([':pid' => $post_id]);
        echo json_encode([
            "success" => true,
            "status" => "success",
            "counted" => false,
            "shares_count" => (int)$countStmt->fetchColumn()
        ]);
        exit;
    }

    $db->beginTransaction();
    $ins = $db->prepare("
        INSERT INTO post_share_opens (post_id, viewer_user_id, share_link_id)
        VALUES (:pid, :vid, :slid)
        ON CONFLICT (post_id, viewer_user_id) DO NOTHING
    ");
    $ins->execute([
        ':pid' => $post_id,
        ':vid' => $viewer_id,
        ':slid' => $share_link_id
    ]);
    $counted = $ins->rowCount() > 0;

    if ($counted) {
        $inc = $db->prepare("UPDATE posts SET share_count = COALESCE(share_count, 0) + 1 WHERE post_id = :pid");
        $inc->execute([':pid' => $post_id]);
    }

    $countStmt = $db->prepare("SELECT COALESCE(share_count, 0) FROM posts WHERE post_id = :pid");
    $countStmt->execute([':pid' => $post_id]);
    $sharesCount = (int)$countStmt->fetchColumn();
    $db->commit();

    echo json_encode([
        "success" => true,
        "status" => "success",
        "counted" => $counted,
        "shares_count" => $sharesCount
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

