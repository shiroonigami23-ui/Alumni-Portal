<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

if ($event_id) {
    // Join RSVPs with Profiles to get names and avatars
    $query = "SELECT p.full_name, p.profile_picture_url, p.branch, r.rsvp_status 
              FROM event_rsvps r 
              JOIN profiles p ON r.user_id = p.user_id 
              WHERE r.event_id = :eid";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['eid' => $event_id]);
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "event_id" => $event_id,
        "attendee_count" => count($attendees),
        "attendees" => $attendees
    ]);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Event ID is required."]);
}
?>