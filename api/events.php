<?php
require_once __DIR__ . '/_kernel.php';
// Alumni propose events (staff approve them); faculty/admin events go live straight away.
$api = api_endpoint(['access' => 'member', 'actions' => [
    'create' => ['alumni', 'faculty', 'admin'], 'pending' => 'staff', 'approve' => 'staff', 'reject' => 'staff',
]]);   // who may call this — enforced before anything below
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Event.php';
include_once '../middleware/Security.php';
require_once __DIR__ . '/_privacy.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Validate Request
$user_id = $auth->validateRequest();
$event = new Event($db);

$data = json_decode(file_get_contents("php://input"));
$action = $api->action;

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
                $st = $db->prepare("SELECT status::text FROM events WHERE event_id = :e");
                $st->execute([':e' => $id]);
                $status = (string)$st->fetchColumn();
                echo json_encode(["success" => true, "event_id" => $id, "status" => $status,
                    "message" => $status === 'approved' ? "Event created." : "Event sent for approval. It appears once a faculty member or admin approves it."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create event."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "A title and a date are required."]);
        }
        break;

    case 'pending':   // staff review queue, newest first, with who proposed it — one query
    case 'mine':      // my own events in any state (so alumni can see "awaiting approval" / "not approved")
        $where = $action === 'pending' ? "e.status = 'pending_approval'::event_status" : "e.creator_user_id = :me";
        $st = $db->prepare("
            SELECT e.event_id, e.title, e.description, e.start_datetime, e.end_datetime, e.location_address,
                   e.status::text AS status, e.created_at, e.creator_user_id,
                   COALESCE(NULLIF(TRIM(p.full_name), ''), 'RJIT member') AS creator_name, u.username AS creator_username, u.role AS creator_role
            FROM events e
            JOIN users u ON u.user_id = e.creator_user_id
            LEFT JOIN profiles p ON p.user_id = e.creator_user_id
            WHERE $where
            ORDER BY e.created_at DESC
            LIMIT 200
        ");
        $st->execute($action === 'pending' ? [] : [':me' => $api->uid]);
        api_ok(array_map('sanitizeEvent', $st->fetchAll(PDO::FETCH_ASSOC)));

    case 'approve':
    case 'reject':
        $eventId = $api->int('event_id', true);
        $st = $db->prepare("SELECT e.event_id, e.title, e.creator_user_id, e.status::text AS status, u.role AS creator_role
                            FROM events e JOIN users u ON u.user_id = e.creator_user_id WHERE e.event_id = :e");
        $st->execute([':e' => $eventId]);
        $ev = $st->fetch(PDO::FETCH_ASSOC);
        if (!$ev) {
            api_fail(404, 'Event not found.');
        }
        if ($ev['status'] !== 'pending_approval') {
            api_fail(409, 'This event has already been reviewed.');
        }
        $action === 'approve' ? $event->approve($eventId) : $event->reject($eventId);
        $why = $api->str('reason', 300);
        privacy_notify($db, (int)$ev['creator_user_id'], $api->uid, 'event', $action === 'approve'
            ? "Your event \"{$ev['title']}\" was approved and is now live."
            : "Your event \"{$ev['title']}\" wasn't approved" . ($why !== '' ? ": $why" : '.'));
        $api->log($action === 'approve' ? 'APPROVE_EVENT' : 'REJECT_EVENT', "Event #$eventId ({$ev['title']})" . ($why !== '' ? " — $why" : ''));
        api_ok(['event_id' => $eventId, 'status' => $action === 'approve' ? 'approved' : 'rejected'],
            $action === 'approve' ? 'Event approved — it\'s live now.' : 'Event declined. The organiser was told.');

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
            if (!in_array((string)$data->status, ['going', 'attending', 'interested', 'maybe', 'not_going', 'cancelled'], true)) {
                api_fail(400, 'status must be going, attending, interested, maybe, not_going or cancelled.');
            }
            $ok = $db->prepare("SELECT 1 FROM events WHERE event_id = :e AND status = 'approved'::event_status");
            $ok->execute([':e' => (int)$data->event_id]);
            if (!$ok->fetchColumn()) {
                api_fail(404, 'This event isn\'t open for RSVPs.');
            }
            if ($event->rsvp($data->event_id, $user_id, $data->status)) {
                echo json_encode(["message" => "RSVP updated."]);
            } else {
                echo json_encode(["message" => "Failed to update RSVP."]);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        break;
}
