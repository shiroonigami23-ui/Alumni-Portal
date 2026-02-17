<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Event.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Validate Token
$user_id = $auth->validateRequest();

$event = new Event($db);
$limit = $_GET['limit'] ?? 10;

// Get upcoming events
$query = "SELECT e.*, p.full_name as organizer_name
          FROM events e
          LEFT JOIN users u ON e.created_by = u.user_id
          LEFT JOIN profiles p ON u.user_id = p.user_id
          WHERE e.event_date >= CURRENT_DATE
          AND e.status = 'approved'
          ORDER BY e.event_date ASC
          LIMIT :limit";

$stmt = $db->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);
