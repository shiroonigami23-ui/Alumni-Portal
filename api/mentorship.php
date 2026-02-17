<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Mentorship.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest(); // Returns user_id if valid
$mentorship = new Mentorship($db);

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'request':
        if (!empty($data->mentor_id) && !empty($data->message)) {
            $result = $mentorship->createRequest($user_id, $data->mentor_id, $data->message);
            echo json_encode($result);
        } else {
            echo json_encode(["message" => "Missing mentor_id or message."]);
        }
        break;

    case 'respond':
        if (!empty($data->request_id) && !empty($data->status)) {
            if ($mentorship->updateStatus($data->request_id, $user_id, $data->status)) {
                echo json_encode(["message" => "Request updated."]);
            } else {
                echo json_encode(["message" => "Failed to update request."]);
            }
        }
        break;

    case 'list_requests':
        // For mentors to see who requested them
        $requests = $mentorship->getRequestsForMentor($user_id);
        echo json_encode($requests);
        break;

    default:
        echo json_encode(["message" => "Invalid action."]);
        break;
}
