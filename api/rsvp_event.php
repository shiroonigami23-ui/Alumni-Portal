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

if (!empty($data->event_id) && !empty($data->status)) {
    
    // Architect Note: Aligning with existing DB schema (using 'rsvp_status')
    // Valid values assumed from standard logic, but let's allow string input to be safe
    $status = $data->status;

    // Upsert Logic: Insert if new, Update if exists
    // We check for the UNIQUE constraint on (event_id, user_id)
    $query = "INSERT INTO event_rsvps (event_id, user_id, rsvp_status) 
              VALUES (:eid, :uid, :status)
              ON CONFLICT (event_id, user_id) 
              DO UPDATE SET rsvp_status = :status, created_at = CURRENT_TIMESTAMP";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['eid' => $data->event_id, 'uid' => $user_id, 'status' => $status])) {
        echo json_encode(["message" => "RSVP updated successfully to " . $status]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error. Check if Event ID exists."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Event ID and Status required."]);
}
?>