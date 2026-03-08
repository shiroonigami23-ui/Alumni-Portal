<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_message_columns($db);
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();

    $payload = json_decode(file_get_contents("php://input"), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $messageId = isset($payload['message_id']) ? (int)$payload['message_id'] : 0;
    if ($messageId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'message_id is required']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT message_id, sender_user_id, deleted_at
        FROM messages
        WHERE message_id = :mid
        LIMIT 1
    ");
    $stmt->execute([':mid' => $messageId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        exit;
    }
    if ((int)$row['sender_user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can delete only your own messages']);
        exit;
    }
    if (!empty($row['deleted_at'])) {
        echo json_encode(['success' => true, 'message' => 'Already deleted']);
        exit;
    }

    $upd = $db->prepare("UPDATE messages SET deleted_at = NOW() WHERE message_id = :mid");
    $upd->execute([':mid' => $messageId]);

    echo json_encode([
        'success' => true,
        'message' => 'Message deleted',
        'data' => ['message_id' => $messageId]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete message',
        'error' => $e->getMessage()
    ]);
}

