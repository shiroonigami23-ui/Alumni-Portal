<?php
/**
 * Get Events API
 * Retrieve events with filters (upcoming, past, pending, user's events)
 */

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../models/Event.php';

header('Content-Type: application/json');

// Connect to database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed']);
    exit;
}

// Authenticate user (optional for public events)
$user = null;
$headers = function_exists('apache_request_headers') ? apache_request_headers() : $_SERVER;
$authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null));

if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader)) {
    $auth = new Auth($db);
    $user_id = $auth->validateRequest();
    $user_stmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = :uid");
    $user_stmt->execute(['uid' => $user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
}

// Get query parameters
$filter = $_GET['filter'] ?? 'upcoming'; // upcoming, past, pending, my_events, all
$user_id = $_GET['user_id'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$eventModel = new Event($db);

// Handle "my_events" filter
if ($filter === 'my_events') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => 'Authentication required for my_events filter']);
        exit;
    }
    $events = $eventModel->getUserEvents($user['user_id'], 'upcoming');
} else {
    // Handle pending events (admin/faculty only)
    if ($filter === 'pending') {
        if (!$user || !in_array($user['role'], ['admin', 'faculty'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Only admin/faculty can view pending events']);
            exit;
        }
    }
    
    $events = $eventModel->getEvents($filter, $user_id, $limit, $offset);
}

// Read description files and add to response
foreach ($events as &$event) {
    if ($event['description'] && file_exists($event['description'])) {
        $event['description_content'] = file_get_contents($event['description']);
    } else {
        $event['description_content'] = $event['description'];
    }
    
    // Check if current user has RSVP'd
    if ($user) {
        $rsvp = $eventModel->hasRsvp($event['event_id'], $user['user_id']);
        $event['user_rsvp'] = $rsvp ? $rsvp['rsvp_status'] : null;
    } else {
        $event['user_rsvp'] = null;
    }
}

echo json_encode([
    'count' => count($events),
    'filter' => $filter,
    'events' => $events
]);
