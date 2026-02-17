<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once "../config/Database.php";
include_once "../middleware/Auth.php";

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->content) && !empty($data->publish_at)) {
    $filename = "sched_" . $user_id . "_" . time() . ".txt";
    $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "posts" . DIRECTORY_SEPARATOR;
    if (!file_exists($storage_dir)) { mkdir($storage_dir, 0777, true); }
    
    $file_path = "storage/posts/" . $filename;
    
    if (file_put_contents($storage_dir . $filename, $data->content)) {
        $query = "INSERT INTO scheduled_posts (user_id, content_path, type, publish_at) 
                  VALUES (:uid, :path, :type, :publish)";
        
        $stmt = $db->prepare($query);
        $execution = $stmt->execute([
            "uid" => $user_id,
            "path" => $file_path,
            "type" => $data->type ?? "text",
            "publish" => $data->publish_at
        ]);

        if ($execution) {
            $auth->logAction($user_id, "SCHEDULE_POST", "Post scheduled for " . $data->publish_at);
            echo json_encode(["message" => "Post scheduled successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to schedule post."]);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Content and publish_at timestamp required."]);
}
?>
