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

if (!empty($data->event_id)) {
    // 1. Delete the record
    $query = "DELETE FROM event_rsvps WHERE event_id = :eid AND user_id = :uid";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['eid' => $data->event_id, 'uid' => $user_id])) {
        if ($stmt->rowCount() > 0) {
            // 2. Decrement the counter
            $db->prepare("UPDATE events SET rsvp_count = rsvp_count - 1 WHERE event_id = :eid AND rsvp_count > 0")
               ->execute(['eid' => $data->event_id]);
            
            echo json_encode(["message" => "RSVP cancelled successfully."]);
        } else {
            echo json_encode(["message" => "No RSVP found to cancel."]);
        }
    }
}
?>