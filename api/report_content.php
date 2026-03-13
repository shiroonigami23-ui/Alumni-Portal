<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

function ensure_comment_reports_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $db->exec("ALTER TABLE comments ADD COLUMN IF NOT EXISTS report_count INTEGER NOT NULL DEFAULT 0");
    $db->exec("
        CREATE TABLE IF NOT EXISTS comment_reports (
            comment_report_id BIGSERIAL PRIMARY KEY,
            comment_id BIGINT NOT NULL REFERENCES comments(comment_id) ON DELETE CASCADE,
            reporter_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            reason VARCHAR(50) NOT NULL DEFAULT 'spam',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (comment_id, reporter_user_id)
        )
    ");
    $done = true;
}

function ensure_post_reports_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    // Remove duplicate legacy reports so unique index can be created safely.
    $db->exec("
        DELETE FROM reports r
        USING reports r2
        WHERE r.ctid < r2.ctid
          AND r.reported_post_id = r2.reported_post_id
          AND r.reporter_user_id = r2.reporter_user_id
          AND r.reported_post_id IS NOT NULL
    ");
    $db->exec("
        CREATE UNIQUE INDEX IF NOT EXISTS uq_reports_post_reporter
        ON reports (reported_post_id, reporter_user_id)
    ");
    $done = true;
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $reporter_id = (int)$auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    $comment_id = isset($data['comment_id']) ? (int)$data['comment_id'] : 0;

    if ($post_id <= 0 && $comment_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id or comment_id is required"]);
        exit;
    }

    $u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $u_stmt->execute(['uid' => $reporter_id]);
    $role = (string)$u_stmt->fetchColumn();

    if ($post_id > 0) {
        ensure_post_reports_schema($db);
        $db->beginTransaction();
        if ($role === 'admin') {
            $db->prepare("DELETE FROM posts WHERE post_id = :pid")->execute(['pid' => $post_id]);
            $db->commit();
            echo json_encode(["success" => true, "status" => "success", "message" => "Architect Directive: Post purged."]);
            exit;
        }

        $query = "INSERT INTO reports (reported_post_id, reporter_user_id, reason, status)
                  VALUES (:pid, :rid, 'spam'::report_reason, 'pending'::report_status)
                  ON CONFLICT (reported_post_id, reporter_user_id) DO NOTHING";
        $ins = $db->prepare($query);
        $ins->execute(['pid' => $post_id, 'rid' => $reporter_id]);
        $isNew = $ins->rowCount() > 0;

        if ($isNew) {
            $db->prepare("
                UPDATE posts
                SET report_count = (
                    SELECT COUNT(*)
                    FROM reports
                    WHERE reported_post_id = :pid
                )
                WHERE post_id = :pid
            ")->execute(['pid' => $post_id]);
        }

        $count_stmt = $db->prepare("SELECT COALESCE(report_count, 0) FROM posts WHERE post_id = :pid");
        $count_stmt->execute(['pid' => $post_id]);
        $current = (int)$count_stmt->fetchColumn();

        $message = "Already reported by you.";
        if ($isNew && $current >= 5) {
            $db->prepare("UPDATE posts SET status = 'shadow_banned'::post_status WHERE post_id = :pid")->execute(['pid' => $post_id]);
            $message = "Report submitted. Post sent for review.";
        } elseif ($isNew) {
            $message = "Report submitted.";
        }
        $db->commit();
        echo json_encode(["success" => true, "status" => "success", "message" => $message]);
        exit;
    }

    ensure_comment_reports_schema($db);
    $db->beginTransaction();
    if ($role === 'admin') {
        $db->prepare("DELETE FROM comments WHERE comment_id = :cid")->execute(['cid' => $comment_id]);
        $db->commit();
        echo json_encode(["success" => true, "status" => "success", "message" => "Architect Directive: Comment purged."]);
        exit;
    }

    $ins = $db->prepare("
        INSERT INTO comment_reports (comment_id, reporter_user_id, reason, status)
        VALUES (:cid, :rid, 'spam', 'pending')
        ON CONFLICT (comment_id, reporter_user_id) DO NOTHING
    ");
    $ins->execute([':cid' => $comment_id, ':rid' => $reporter_id]);
    $isNew = $ins->rowCount() > 0;
    if ($isNew) {
        $db->prepare("UPDATE comments SET report_count = COALESCE(report_count, 0) + 1 WHERE comment_id = :cid")
           ->execute([':cid' => $comment_id]);
    }

    $cnt = $db->prepare("SELECT COALESCE(report_count, 0) FROM comments WHERE comment_id = :cid");
    $cnt->execute([':cid' => $comment_id]);
    $current = (int)$cnt->fetchColumn();
    $db->commit();

    if (!$isNew) {
        echo json_encode(["success" => true, "status" => "success", "message" => "Already reported by you."]);
    } else {
        echo json_encode(["success" => true, "status" => "success", "message" => "Comment report logged. Current count: {$current}"]);
    }
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "Moderation Error: " . $e->getMessage()]);
}
?>
