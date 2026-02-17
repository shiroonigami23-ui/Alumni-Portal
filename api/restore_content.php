<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$admin_id = $auth->validateRequest();

// Verify Admin Role
$u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$u_stmt->execute(['uid' => $admin_id]);
if ($u_stmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Only the Architect can restore content."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->post_id)) {
    // Cast 'published' to post_status ENUM and lock the post
    $query = "UPDATE posts SET 
              status = 'published'::post_status, 
              report_count = 0, 
              is_restored = true 
              WHERE post_id = :pid";
    
    $stmt = $db->prepare($query);
    if ($stmt->execute(['pid' => $data->post_id])) {
        echo json_encode(["message" => "Content restored and granted Architect Immunity."]);
    }
}
?>