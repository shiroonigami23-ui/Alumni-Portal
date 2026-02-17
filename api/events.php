<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Event.php';
include_once '../middleware/Security.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Validate Request
$user_id = $auth->validateRequest();
$event = new Event($db);

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

// Basic output sanitization for list responses
function sanitizeEvent($e)
{
    return Security::sanitizeInput($e);
}

switch ($action) {
    case 'create':
        // CSRF Check for state changing actions
        Security::checkCSRF();

        if (!empty($data->title) && !empty($data->event_date)) {
            $id = $event->create(
                $user_id,
                $data->title,
                $data->description ?? '',
                $data->event_date,
                $data->event_time ?? '00:00:00',
                $data->end_date ?? null,
                $data->end_time ?? null,
                $data->location ?? 'TBD',
                $data->visibility ?? 'public',
                $data->rsvp_limit ?? null,
                $data->banner_url ?? null,
                $data->live_stream_url ?? null,
                $data->comments_enabled ?? true
            );

            if ($id) {
                echo json_encode(["message" => "Event created.", "event_id" => $id]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create event."]);
            }
        } else {
            echo json_encode(["message" => "Incomplete data."]);
        }
        break;

    case 'list':
        $filter = $_GET['filter'] ?? 'upcoming';
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;

        $events = $event->getEvents($filter, null, $limit, $offset);

        // Sanitize output to prevent XSS
        $safe_events = array_map('sanitizeEvent', $events);

        echo json_encode($safe_events);
        break;

    case 'rsvp':
        Security::checkCSRF();

        if (!empty($data->event_id) && !empty($data->status)) {
            if ($event->rsvp($data->event_id, $user_id, $data->status)) {
                echo json_encode(["message" => "RSVP updated."]);
            } else {
                echo json_encode(["message" => "Failed to update RSVP."]);
            }
        }
        break;

    default:
        echo json_encode(["message" => "Invalid action."]);
        break;
}
