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

echo json_encode([
    "unread_count" => $unread_count,
    "notifications" => $notifications
]);
?>