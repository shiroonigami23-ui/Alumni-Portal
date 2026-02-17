<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->post_id) && !empty($data->parent_id) && !empty($data->content)) {
    
    // 1. Get Parent Info to calculate depth
    $parent_stmt = $db->prepare("SELECT depth_level FROM comments WHERE comment_id = :pid");
    $parent_stmt->execute(['pid' => $data->parent_id]);
    $parent = $parent_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent || $parent['depth_level'] >= 5) {
        http_response_code(400);
        echo json_encode(["message" => "Maximum thread depth reached or parent missing."]);
        exit();
    }

    $new_depth = $parent['depth_level'] + 1;

    // 2. 3.5NF Storage Logic
    $filename = "reply_" . $user_id . "_" . time() . ".txt";
    $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "comments" . DIRECTORY_SEPARATOR . "attachments" . DIRECTORY_SEPARATOR;
    $relative_path = "storage/comments/attachments/" . $filename;

    if (file_put_contents($storage_dir . $filename, $data->content)) {
        // 3. Insert into DB
        $query = "INSERT INTO comments (post_id, user_id, parent_comment_id, content_file_path, depth_level) 
                  VALUES (:post_id, :uid, :parent_id, :path, :depth)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            'post_id' => $data->post_id,
            'uid' => $user_id,
            'parent_id' => $data->parent_id,
            'path' => $relative_path,
            'depth' => $new_depth
        ])) {
            echo json_encode(["message" => "Reply added successfully.", "depth" => $new_depth]);
        }
    }
}
?>