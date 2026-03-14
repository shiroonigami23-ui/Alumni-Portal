<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';
require_once __DIR__ . '/_message_content.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_message_columns($db);
    ensure_group_message_schema($db);
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();

    $payload = json_decode(file_get_contents("php://input"), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $messageId = isset($payload['message_id']) ? (int)$payload['message_id'] : 0;
    $messageScope = strtolower(trim((string)($payload['message_scope'] ?? 'direct')));
    $message = trim((string)($payload['message'] ?? ''));

    if ($messageId <= 0 || $message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'message_id and message are required']);
        exit;
    }

    $table = $messageScope === 'group' ? 'mentorship_group_messages' : 'messages';

    $stmt = $db->prepare("
        SELECT message_id, sender_user_id, content_file_path, created_at, deleted_at
        FROM {$table}
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
        echo json_encode(['success' => false, 'message' => 'You can edit only your own messages']);
        exit;
    }
    if (!empty($row['deleted_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Deleted message cannot be edited']);
        exit;
    }
    if (strtotime((string)$row['created_at']) < strtotime('-30 minutes')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Edit window expired (30 minutes)']);
        exit;
    }

    $existing = load_message_payload_record($db, (string)$row['content_file_path']);
    $nextPointer = update_message_payload_record(
        $db,
        (string)$row['content_file_path'],
        $message,
        !empty($existing['attachment']) && is_array($existing['attachment']) ? $existing['attachment'] : null,
        $userId
    );

    if ($nextPointer === '') {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update message content']);
        exit;
    }

    $pathStmt = $db->prepare("UPDATE {$table} SET content_file_path = :path WHERE message_id = :mid");
    $pathStmt->execute([
        ':path' => $nextPointer,
        ':mid' => $messageId
    ]);

    $upd = $db->prepare("UPDATE {$table} SET edited_at = NOW() WHERE message_id = :mid");
    $upd->execute([':mid' => $messageId]);

    echo json_encode([
        'success' => true,
        'message' => 'Message edited',
        'data' => [
            'message_id' => $messageId,
            'edited_at' => date('c')
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to edit message',
        'error' => $e->getMessage()
    ]);
}
