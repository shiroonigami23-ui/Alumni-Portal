<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

if (!empty($_GET['contact_id'])) {
    $contact_id = $_GET['contact_id'];

    // 1. Fetch metadata for messages between these two users
    $query = "SELECT * FROM messages 
              WHERE (sender_user_id = :uid AND receiver_user_id = :cid) 
              OR (sender_user_id = :cid AND receiver_user_id = :uid) 
              ORDER BY created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['uid' => $user_id, 'cid' => $contact_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $history = [];
    foreach ($messages as $msg) {
        $full_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $msg['content_file_path'];
        
        // 2. Read content from the .txt file (Blueprint Section 1)
        $content = file_exists($full_path) ? file_get_contents($full_path) : "[Content Missing]";

        $history[] = [
            "message_id" => $msg['message_id'],
            "sender_id" => $msg['sender_user_id'],
            "receiver_id" => $msg['receiver_user_id'],
            "message" => $content,
            "timestamp" => $msg['created_at'],
            "is_read" => !is_null($msg['read_at'])
        ];
    }

    echo json_encode($history);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Contact ID required."]);
}
?>
