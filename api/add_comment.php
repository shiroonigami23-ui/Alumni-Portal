<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// 1. Authenticate the User
$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->post_id) && !empty($data->content)) {
    
    // 2. 3.5NF Logic: Create physical text file for the comment
    $filename = "cmt_" . $user_id . "_" . time() . ".txt";
    $relative_path = "storage/comments/" . $filename;
    $absolute_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "comments" . DIRECTORY_SEPARATOR;

    // Ensure the comments directory exists
    if (!file_exists($absolute_dir)) { 
        mkdir($absolute_dir, 0777, true); 
    }

    if (file_put_contents($absolute_dir . $filename, $data->content)) {
        
        // 3. Insert metadata into comments table and get the new ID
        // Using RETURNING comment_id for PostgreSQL specifically
        $query = "INSERT INTO comments (post_id, user_id, content_file_path) 
                  VALUES (:pid, :uid, :path) RETURNING comment_id";
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute(['pid' => $data->post_id, 'uid' => $user_id, 'path' => $relative_path])) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $comment_id = $result['comment_id'];

            // 4. NOTIFICATION ENGINE: Notify the Post Author
            // Find out who owns the post
            $post_stmt = $db->prepare("SELECT user_id, title FROM posts WHERE post_id = :pid");
            $post_stmt->execute(['pid' => $data->post_id]);
            $post = $post_stmt->fetch(PDO::FETCH_ASSOC);

            // Trigger notification only if the commenter is NOT the post owner
            if ($post && $post['user_id'] != $user_id) {
                $notif_query = "INSERT INTO notifications (user_id, notification_type, related_post_id, related_comment_id, content) 
                                VALUES (:target, 'comment', :pid, :cid, :msg)";
                
                $notif_stmt = $db->prepare($notif_query);
                $notif_stmt->execute([
                    'target' => $post['user_id'],
                    'pid' => $data->post_id,
                    'cid' => $comment_id,
                    'msg' => "New comment on your post '" . $post['title'] . "'"
                ]);
            }

            echo json_encode([
                "message" => "Comment saved and author notified.",
                "comment_id" => $comment_id,
                "file" => $filename
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Database metadata save failed."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to write comment to storage."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. post_id and content required."]);
}
?>