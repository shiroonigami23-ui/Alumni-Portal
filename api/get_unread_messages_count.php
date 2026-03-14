<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_message_columns($db);
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();

    $stmt = $db->prepare("
        SELECT COUNT(*)::int
        FROM messages
        WHERE receiver_user_id = :uid
          AND read_at IS NULL
          AND deleted_at IS NULL
    ");
    $stmt->execute([':uid' => $userId]);

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
        'message' => 'Failed to load unread messages count.'
    ]);
}
