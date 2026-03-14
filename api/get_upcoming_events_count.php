<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $auth->validateRequest();

    $stmt = $db->query("
        SELECT COUNT(*)::int
        FROM events
        WHERE DATE(start_datetime) >= CURRENT_DATE
          AND status::text = 'approved'
    ");

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'count' => (int)$stmt->fetchColumn()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Failed to load upcoming events count.'
    ]);
}
