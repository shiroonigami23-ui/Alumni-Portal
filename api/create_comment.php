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

if (!empty($data->post_id) && !empty($data->content)) {
    // 3.5NF: Save comment text to file
    $filename = "comm_" . $user_id . "_" . time() . ".txt";
    $dir = dirname(__DIR__) . "/storage/comments/";
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    
    file_put_contents($dir . $filename, $data->content);
    $file_path = "storage/comments/" . $filename;

    $query = "INSERT INTO comments (post_id, user_id, content_file_path) VALUES (:pid, :uid, :path)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['pid' => $data->post_id, 'uid' => $user_id, 'path' => $file_path])) {
        // Increment comment count in posts table
        $db->prepare("UPDATE posts SET comment_count = comment_count + 1 WHERE post_id = :pid")
           ->execute(['pid' => $data->post_id]);
           
        echo json_encode(["message" => "Comment added successfully."]);
    }
}
?>
