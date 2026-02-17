<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$reporter_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->post_id)) {
    try {
        $db->beginTransaction();

        $u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
        $u_stmt->execute(['uid' => $reporter_id]);
        $role = $u_stmt->fetchColumn();

        if ($role === 'admin') {
            $db->prepare("DELETE FROM posts WHERE post_id = :pid")->execute(['pid' => $data->post_id]);
            $db->commit();
            echo json_encode(["message" => "Architect Directive: Post purged."]);
            exit();
        }

        // Standard Report Logic
        $query = "INSERT INTO reports (reported_post_id, reporter_user_id, reason, status) 
                  VALUES (:pid, :rid, 'spam'::report_reason, 'pending'::report_status)";
        $db->prepare($query)->execute(['pid' => $data->post_id, 'rid' => $reporter_id]);

        $db->prepare("UPDATE posts SET report_count = report_count + 1 WHERE post_id = :pid")->execute(['pid' => $data->post_id]);
        
        $count_stmt = $db->prepare("SELECT report_count FROM posts WHERE post_id = :pid");
        $count_stmt->execute(['pid' => $data->post_id]);
        $current = (int)$count_stmt->fetchColumn();

        $message = "Report logged. Current count: $current";

        if ($current >= 5) {
            // VERIFIED ENUM: shadow_banned
            $db->prepare("UPDATE posts SET status = 'shadow_banned'::post_status WHERE post_id = :pid")->execute(['pid' => $data->post_id]);
            $message = "Threshold reached. Post shadow_banned for review.";
        }

        $db->commit();
        echo json_encode(["message" => $message]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => "Moderation Error: " . $e->getMessage()]);
    }
}
?>