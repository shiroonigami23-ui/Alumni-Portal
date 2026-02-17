<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// 1. Fetch conversations
$query = "SELECT DISTINCT ON (partner_id) 
            CASE WHEN sender_user_id = :uid THEN receiver_user_id ELSE sender_user_id END as partner_id,
            message_id, content_file_path, created_at, read_at, sender_user_id
          FROM messages 
          WHERE sender_user_id = :uid OR receiver_user_id = :uid
          ORDER BY partner_id, created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute(['uid' => $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$inbox = [];

foreach ($conversations as $conv) {
    // 2. Hydrate Message Content
    $msg_content = "Content unavailable.";
    $file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $conv['content_file_path']);
    
    if (file_exists($file_path)) {
        $msg_content = file_get_contents($file_path);
    }

    // 3. Robust Profile Fetch (Fixes the Warning)
    $prof_stmt = $db->prepare("SELECT full_name, profile_picture_url FROM profiles WHERE user_id = :pid");
    $prof_stmt->execute(['pid' => $conv['partner_id']]);
    $partner_info = $prof_stmt->fetch(PDO::FETCH_ASSOC);

    // ARCHITECT FIX: Check if $partner_info is actually an array before accessing keys
    $name = ($partner_info && isset($partner_info['full_name'])) ? $partner_info['full_name'] : "User #" . $conv['partner_id'];
    $avatar = ($partner_info && isset($partner_info['profile_picture_url'])) ? $partner_info['profile_picture_url'] : null;

    $inbox[] = [
        "conversation_with" => $name,
        "partner_id" => $conv['partner_id'],
        "avatar" => $avatar,
        "last_message" => $msg_content,
        "is_sent_by_me" => ($conv['sender_user_id'] == $user_id),
        "is_read" => !is_null($conv['read_at']),
        "time" => $conv['created_at']
    ];
}

echo json_encode(["count" => count($inbox), "messages" => $inbox]);
?>