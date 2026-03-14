<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';
require_once __DIR__ . '/_moderation_schema.php';

function build_room_code(int $a, int $b, string $type): string
{
    $min = min($a, $b);
    $max = max($a, $b);
    $stamp = time();
    return "RJIT-{$type}-{$min}-{$max}-{$stamp}";
}

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_calls_table($db);
    ensure_user_moderation_schema($db);
    $auth = new Auth($db);
    $initiatorId = (int)$auth->validateRequest();

    $payload = json_decode(file_get_contents("php://input"), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $receiverId = isset($payload['receiver_id']) ? (int)$payload['receiver_id'] : 0;
    $callType = strtolower(trim((string)($payload['call_type'] ?? 'audio')));
    if (!in_array($callType, ['audio', 'video'], true)) {
        $callType = 'audio';
    }

    if ($receiverId <= 0 || $receiverId === $initiatorId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid receiver_id is required']);
        exit;
    }

    $exists = $db->prepare("SELECT user_id FROM users WHERE user_id = :uid LIMIT 1");
    $exists->execute([':uid' => $receiverId]);
    if (!$exists->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Receiver not found']);
        exit;
    }

    moderation_assert_messaging_allowed($db, $initiatorId, 'You are restricted from starting calls right now.');

    $initiatorRole = moderation_get_user_role($db, $initiatorId);
    $receiverRole = moderation_get_user_role($db, $receiverId);
    if ($initiatorRole !== 'admin' && $receiverRole === 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot start direct calls with admin accounts.']);
        exit;
    }

    if (
        $initiatorRole !== 'admin' &&
        in_array($initiatorRole, ['alumni', 'faculty'], true) &&
        moderation_is_profile_private($db, $initiatorId)
    ) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Private accounts must switch to public before starting direct calls.']);
        exit;
    }

    $roomCode = build_room_code($initiatorId, $receiverId, $callType);
    $roomUrl = "https://meet.jit.si/" . rawurlencode($roomCode);

    $ins = $db->prepare("
        INSERT INTO calls (initiator_user_id, receiver_user_id, call_type, room_code, room_url)
        VALUES (:iid, :rid, :ctype, :rcode, :rurl)
        RETURNING call_id
    ");
    $ins->execute([
        ':iid' => $initiatorId,
        ':rid' => $receiverId,
        ':ctype' => $callType,
        ':rcode' => $roomCode,
        ':rurl' => $roomUrl
    ]);
    $callId = (int)$ins->fetchColumn();

    $notif = $db->prepare("
        INSERT INTO notifications (user_id, notification_type, related_user_id, content)
        VALUES (:uid, 'call_invite', :rid, :content)
    ");
    $notif->execute([
        ':uid' => $receiverId,
        ':rid' => $initiatorId,
        ':content' => ucfirst($callType) . " call started. Join from your notifications."
    ]);

    echo json_encode([
        'success' => true,
        'message' => ucfirst($callType) . ' call started',
        'data' => [
            'call_id' => $callId,
            'call_type' => $callType,
            'room_url' => $roomUrl
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to start call',
        'error' => $e->getMessage()
    ]);
}
