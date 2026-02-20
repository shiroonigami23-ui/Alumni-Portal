<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->post_id) || empty($data->action)) {
    http_response_code(400);
    echo json_encode(["message" => "post_id and action (edit/delete/toggle) required."]);
    exit();
}

// 1. Ownership & Time Check
$stmt = $db->prepare("SELECT user_id, created_at FROM posts WHERE post_id = :pid");
$stmt->execute(['pid' => $data->post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post || $post['user_id'] != $user_id) {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized or post not found."]);
    exit();
}

$created_at = strtotime($post['created_at']);
$now = time();
$hours_passed = ($now - $created_at) / 3600;

switch ($data->action) {
    case 'edit':
        // ARCHITECT RULE: 24-hour edit window
        if ($hours_passed > 24) {
            http_response_code(403);
            echo json_encode(["message" => "Edit window expired (24h limit)."]);
            exit();
        }

        // Update post content in its backing file (3.5NF storage design)
        $path_stmt = $db->prepare("SELECT content_file_path FROM posts WHERE post_id = :pid");
        $path_stmt->execute(['pid' => $data->post_id]);
        $content_file_path = $path_stmt->fetchColumn();
        if ($content_file_path && isset($data->content)) {
            $abs_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $content_file_path);
            if (file_exists($abs_path)) {
                file_put_contents($abs_path, $data->content);
            }
        }

        $upd = $db->prepare("UPDATE posts SET title = :title, updated_at = NOW() WHERE post_id = :pid");
        $upd->execute(['title' => $data->title, 'pid' => $data->post_id]);
        echo json_encode(["message" => "Post updated successfully."]);
        break;

    case 'delete':
        // No time limit for deletion in the blueprint
        $del = $db->prepare("DELETE FROM posts WHERE post_id = :pid");
        $del->execute(['pid' => $data->post_id]);
        echo json_encode(["message" => "Post deleted."]);
        break;

    case 'toggle_comments':
        $toggle = $db->prepare("UPDATE posts SET comments_enabled = NOT comments_enabled WHERE post_id = :pid");
        $toggle->execute(['pid' => $data->post_id]);
        echo json_encode(["message" => "Comments visibility toggled."]);
        break;
}
?>
