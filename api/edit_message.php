<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';

function resolve_absolute_path(string $relativePath): string
{
    $clean = str_replace(['\\', "\0"], ['/', ''], $relativePath);
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
}

function read_message_payload_from_file(string $absolutePath): array
{
    if (!is_file($absolutePath)) {
        return ['message' => '', 'attachment' => null];
    }
    $raw = @file_get_contents($absolutePath);
    if ($raw === false) {
        return ['message' => '', 'attachment' => null];
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return [
            'message' => (string)($decoded['message'] ?? ''),
            'attachment' => is_array($decoded['attachment'] ?? null) ? $decoded['attachment'] : null
        ];
    }
    return ['message' => (string)$raw, 'attachment' => null];
}

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

    $absPath = resolve_absolute_path((string)$row['content_file_path']);
    $existing = read_message_payload_from_file($absPath);
    $nextPayload = [
        'message' => $message
    ];
    if (!empty($existing['attachment']) && is_array($existing['attachment'])) {
        $nextPayload['attachment'] = $existing['attachment'];
    }
    $serialized = json_encode($nextPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_file($absPath) || $serialized === false || file_put_contents($absPath, $serialized) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update message content']);
        exit;
    }

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
