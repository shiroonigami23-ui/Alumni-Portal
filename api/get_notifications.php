<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// Blueprint Section 10.B: Fetch notification queue with Profile Join
// Corrected to use 'read_at' instead of 'read'
$query = "SELECT n.*, p.full_name as from_user_name 
          FROM notifications n
          LEFT JOIN profiles p ON n.related_user_id = p.user_id
          WHERE n.user_id = :uid 
          ORDER BY n.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute(['uid' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach($notifications as $n) {
    // Logic: If read_at is null, it's unread
    if (is_null($n['read_at'])) $unread_count++;
}

$data = array_map(function ($n) {
    $icon = 'bell';
    if (($n['notification_type'] ?? '') === 'new_comment') $icon = 'message-square';
    if (($n['notification_type'] ?? '') === 'new_like') $icon = 'heart';
    if (($n['notification_type'] ?? '') === 'new_message') $icon = 'mail';
    if (($n['notification_type'] ?? '') === 'connection_request') $icon = 'user-plus';
    return [
        'notification_id' => $n['notification_id'] ?? null,
        'message' => $n['content'] ?? 'New notification',
        'icon' => $icon,
        'created_at' => $n['created_at'] ?? null,
        'read_at' => $n['read_at'] ?? null
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
