<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';
require_once __DIR__ . '/_moderation_schema.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_group_message_schema($db);
    ensure_group_call_schema($db);
    expire_stale_group_calls($db);
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();
    $userRole = moderation_get_user_role($db, $userId);

    $payload = json_decode(file_get_contents("php://input"), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $groupId = isset($payload['group_id']) ? (int)$payload['group_id'] : 0;
    if ($groupId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'group_id is required']);
        exit;
    }

    $groupStmt = $db->prepare("
        SELECT gm.member_role
        FROM mentorship_group_members gm
        WHERE gm.group_id = :gid
          AND gm.user_id = :uid
        LIMIT 1
    ");
    $groupStmt->execute([
        ':gid' => $groupId,
        ':uid' => $userId
    ]);
    $memberRole = (string)$groupStmt->fetchColumn();
    if ($memberRole === '') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not a member of this mentor group.']);
        exit;
    }

    $callStmt = $db->prepare("
        SELECT group_call_id, initiator_user_id
        FROM mentorship_group_calls
        WHERE group_id = :gid
          AND status = 'active'
        ORDER BY created_at DESC, group_call_id DESC
        LIMIT 1
    ");
    $callStmt->execute([':gid' => $groupId]);
    $call = $callStmt->fetch(PDO::FETCH_ASSOC);
    if (!$call) {
        echo json_encode(['success' => true, 'message' => 'No active mentor space to end.']);
        exit;
    }

    $canEnd = ($userRole === 'admin') || ($memberRole === 'admin') || ((int)$call['initiator_user_id'] === $userId);
    if (!$canEnd) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only the group admin, site admin, or space starter can end this mentor space.']);
        exit;
    }

    $endStmt = $db->prepare("
        UPDATE mentorship_group_calls
        SET status = 'ended',
            updated_at = NOW(),
            ended_at = NOW()
        WHERE group_call_id = :cid
    ");
    $endStmt->execute([':cid' => (int)$call['group_call_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Mentor space ended.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to end mentor space',
        'error' => $e->getMessage()
    ]);
}
