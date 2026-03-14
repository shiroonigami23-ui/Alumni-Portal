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

// Get upcoming events using the current event schema.
$query = "SELECT
            e.*,
            e.creator_user_id AS created_by,
            DATE(e.start_datetime) AS event_date,
            TO_CHAR(e.start_datetime, 'HH24:MI:SS') AS event_time,
            DATE(e.end_datetime) AS end_date,
            TO_CHAR(e.end_datetime, 'HH24:MI:SS') AS end_time,
            COALESCE(e.location_address, e.virtual_link) AS location,
            e.banner_image_url AS banner_url,
            p.full_name AS organizer_name
          FROM events e
          LEFT JOIN users u ON e.creator_user_id = u.user_id
          LEFT JOIN profiles p ON u.user_id = p.user_id
          WHERE DATE(e.start_datetime) >= CURRENT_DATE
            AND e.status = 'approved'::event_status
          ORDER BY e.start_datetime ASC
          LIMIT :limit";

$stmt = $db->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);
