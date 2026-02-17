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
    $check = $db->prepare("SELECT status FROM events WHERE event_id = :eid");
    $check->execute(['eid' => $data->event_id]);
    $event = $check->fetch(PDO::FETCH_ASSOC);

    if ($event['status'] !== 'approved') {
        http_response_code(400);
        echo json_encode(["message" => "Event is not open for RSVPs."]);
        exit();
    }

    // ARCHITECT FIX: Using 'attending' to satisfy the ENUM constraint
    $query = "INSERT INTO event_rsvps (event_id, user_id, rsvp_status) 
              VALUES (:eid, :uid, 'attending') 
              ON CONFLICT (event_id, user_id) DO UPDATE SET rsvp_status = 'attending'";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['eid' => $data->event_id, 'uid' => $user_id])) {
        // Increment RSVP count
        $db->prepare("UPDATE events SET rsvp_count = rsvp_count + 1 WHERE event_id = :eid")->execute(['eid' => $data->event_id]);
        echo json_encode(["message" => "Success! You are now attending the event."]);
    }
}
?>