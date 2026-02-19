<?php
/**
 * Create Event API
 * Allows faculty and alumni to create events
 * Faculty events are auto-approved, alumni events need approval
 */

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../models/Event.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Connect to database and authenticate user
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed']);
    exit;
}

$auth = new Auth($db);
$user_id = $auth->validateRequest();

$user_stmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = :uid");
$user_stmt->execute(['uid' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

// Check if user is allowed to create events (alumni, faculty, admin)
if (!in_array($user['role'], ['alumni', 'faculty', 'admin'])) {
    http_response_code(403);
    echo json_encode(['message' => 'Only alumni, faculty, and admin can create events']);
    exit;
}

// Validate required fields
if (!isset($data['title']) || !isset($data['description']) || !isset($data['event_date'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required fields: title, description, event_date']);
    exit;
}

$eventModel = new Event($db);

// Handle description file storage
$description_file_path = null;
if (!empty($data['description'])) {
    $events_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'events';
    if (!is_dir($events_dir)) {
        mkdir($events_dir, 0755, true);
    }
    $description_filename = 'event_desc_' . time() . '.txt';
    $description_absolute_path = $events_dir . DIRECTORY_SEPARATOR . $description_filename;
    file_put_contents($description_absolute_path, $data['description']);
    $description_file_path = 'storage/events/' . $description_filename;
}

// Extract event data
$title = trim($data['title']);
$description = $description_file_path; // Store file path, not content
$event_date = $data['event_date'];
$event_time = $data['event_time'] ?? '00:00:00';
$end_date = $data['end_date'] ?? null;
$end_time = $data['end_time'] ?? null;
$location = $data['location'] ?? 'TBD';
$visibility = $data['visibility'] ?? 'public';
$rsvp_limit = $data['rsvp_limit'] ?? null;
$banner_url = $data['banner_url'] ?? null;
$live_stream_url = $data['live_stream_url'] ?? null;
$comments_enabled = $data['comments_enabled'] ?? true;

// Validate visibility
if (!in_array($visibility, ['public', 'invite_only'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid visibility. Must be "public" or "invite_only"']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

// Create event
$event_id = $eventModel->create(
    $user['user_id'],
    $title,
    $description,
    $event_date,
    $event_time,
    $end_date,
    $end_time,
    $location,
    $visibility,
    $rsvp_limit,
    $banner_url,
    $live_stream_url,
    $comments_enabled
);

if ($event_id) {
    // Get the created event details
    $event = $eventModel->getById($event_id);
    
    // Send notification based on status
    if ($event['status'] === 'pending_approval') {
        // Notify faculty and admin about pending event
        $notif_query = "INSERT INTO notifications (user_id, notification_type, content, related_event_id)
                       SELECT user_id, 'registration_pending'::notification_type, 
                              :content, :event_id
                       FROM users 
                       WHERE role IN ('faculty', 'admin') AND status = 'active'";
        
        $notif_stmt = $db->prepare($notif_query);
        $notif_stmt->execute([
            'content' => "New event '{$title}' pending approval",
            'event_id' => $event_id
        ]);
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Event created successfully. Pending approval from faculty/admin.',
            'event_id' => $event_id,
            'status' => 'pending_approval'
        ]);
    } else {
        // Event auto-approved (faculty/admin)
        http_response_code(201);
        echo json_encode([
            'message' => 'Event created and published successfully.',
            'event_id' => $event_id,
            'status' => 'approved'
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to create event']);
}
