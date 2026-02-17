<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$admin_id = $auth->validateRequest();

$stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
$stmt->execute(['id' => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Only Admins can approve events."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->event_id)) {
    // UPDATED: Using 'approved' to match your PostgreSQL ENUM labels
    $query = "UPDATE events SET status = 'approved', approved_by_admin_id = :aid, approved_at = CURRENT_TIMESTAMP WHERE event_id = :eid";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['aid' => $admin_id, 'eid' => $data->event_id])) {
        echo json_encode(["message" => "Event successfully set to 'approved' status."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to update event status."]);
    }
}
?>