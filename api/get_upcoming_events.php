<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $auth->validateRequest();

    $limit = max(1, min(20, (int)($_GET['limit'] ?? 10)));

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
                AND e.status::text = 'approved'
              ORDER BY e.start_datetime ASC
              LIMIT :limit";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Failed to load upcoming events.'
    ]);
}
