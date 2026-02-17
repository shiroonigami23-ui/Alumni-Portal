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

if (!empty($data->notification_id)) {
    // Ensure the user actually owns this notification (Security first)
    $query = "UPDATE notifications 
              SET read_at = CURRENT_TIMESTAMP 
              WHERE notification_id = :nid AND user_id = :uid";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['nid' => $data->notification_id, 'uid' => $user_id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "Notification marked as read."]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Notification not found or unauthorized."]);
        }
    }
} else {
    // Bulk action: Mark all as read
    $query = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND read_at IS NULL";
    $stmt = $db->prepare($query);
    $stmt->execute(['uid' => $user_id]);
    echo json_encode(["message" => "All notifications marked as read."]);
}
?>