<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_message_schema.php';

$database = new Database();
$db = $database->getConnection();
ensure_calls_table($db);
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// Blueprint Section 10.B: Fetch notification queue with Profile Join
// Corrected to use 'read_at' instead of 'read'
$query = "
    SELECT
        n.*,
        p.full_name AS from_user_name,
        matched_call.call_id AS related_call_id,
        matched_call.call_type AS related_call_type,
        matched_call.room_url AS related_call_room_url
    FROM notifications n
    LEFT JOIN profiles p ON n.related_user_id = p.user_id
    LEFT JOIN LATERAL (
        SELECT
            c.call_id,
            c.call_type,
            c.room_url
        FROM calls c
        WHERE n.notification_type = 'call_invite'
          AND c.initiator_user_id = n.related_user_id
          AND c.receiver_user_id = n.user_id
          AND c.call_type = CASE WHEN n.content ILIKE 'Video%' THEN 'video' ELSE 'audio' END
          AND c.created_at BETWEEN (n.created_at - INTERVAL '1 day') AND (n.created_at + INTERVAL '1 day')
        ORDER BY ABS(EXTRACT(EPOCH FROM (c.created_at - n.created_at))) ASC, c.call_id DESC
        LIMIT 1
    ) matched_call ON TRUE
    WHERE n.user_id = :uid
    ORDER BY n.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute(['uid' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach($notifications as $n) {
    // Logic: If read_at is null, it's unread
    if (is_null($n['read_at'])) $unread_count++;
}

$data = array_map(function ($n) {
    $type = (string)($n['notification_type'] ?? '');
    $callType = (string)($n['related_call_type'] ?? '');
    $fromUserName = trim((string)($n['from_user_name'] ?? ''));
    $icon = 'bell';
    if ($type === 'new_comment') $icon = 'message-square';
    if ($type === 'new_like') $icon = 'heart';
    if ($type === 'new_message') $icon = 'mail';
    if ($type === 'new_post') $icon = 'newspaper';
    if ($type === 'connection_request') $icon = 'user-plus';
    if ($type === 'call_invite') $icon = $callType === 'video' ? 'video' : 'phone-call';

    $message = (string)($n['content'] ?? 'New notification');
    if ($type === 'call_invite' && $fromUserName !== '') {
        $label = $callType === 'video' ? 'Video' : 'Audio';
        $message = "{$label} call from {$fromUserName}. Click to join.";
    }

    $targetUrl = '';
    $actionType = '';
    if ($type === 'call_invite' && !empty($n['related_call_room_url'])) {
        $targetUrl = (string)$n['related_call_room_url'];
        $actionType = 'join-call';
    }

    return [
        'notification_id' => $n['notification_id'] ?? null,
        'type' => $type,
        'message' => $message,
        'icon' => $icon,
        'created_at' => $n['created_at'] ?? null,
        'read_at' => $n['read_at'] ?? null,
        'from_user_name' => $fromUserName,
        'related_user_id' => isset($n['related_user_id']) ? (int)$n['related_user_id'] : null,
        'call_type' => $callType ?: null,
        'action_type' => $actionType,
        'target_url' => $targetUrl
    ];
}, $notifications);

echo json_encode([
    "success" => true,
    "status" => "success",
    "unread_count" => $unread_count,
    "data" => $data,
    "notifications" => $data
]);
?>
