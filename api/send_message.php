<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$sender_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->receiver_id) && !empty($data->message)) {
    
    // 1. Get Roles for Hierarchy Logic
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :sid");
    $stmt->execute(['sid' => $sender_id]);
    $sender_role = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :rid");
    $stmt->execute(['rid' => $data->receiver_id]);
    $receiver_role = $stmt->fetchColumn();

    // 2. Admin Bypass (Blueprint Section 4.D)
    // Admin is above all; they skip Block and Privacy checks.
    if ($sender_role !== 'admin') {
        
        // 3. Block Check (Blueprint Section 18.2 - Mutual Invisibility)
        // Using verified column names: blocker_user_id, blocked_user_id
        $block_check = $db->prepare("SELECT 1 FROM blocks 
                                     WHERE (blocker_user_id = :sid AND blocked_user_id = :rid) 
                                     OR (blocker_user_id = :rid AND blocked_user_id = :sid)");
        $block_check->execute(['sid' => $sender_id, 'rid' => $data->receiver_id]);
        if ($block_check->fetch()) {
            http_response_code(403);
            echo json_encode(["message" => "Messaging blocked."]);
            exit();
        }

        // 4. Privacy Check (Section 7)
        $p_check = $db->prepare("SELECT is_private FROM profiles WHERE user_id = :rid");
        $p_check->execute(['rid' => $data->receiver_id]);
        $is_private = $p_check->fetchColumn();

        if ($is_private && $sender_id != $data->receiver_id) {
            http_response_code(403);
            echo json_encode(["message" => "Cannot message a private profile."]);
            exit();
        }
    }

    // 5. 3.5NF Storage Logic (Message Content to File)
    $filename = "msg_" . $sender_id . "_" . time() . ".txt";
    $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "messages" . DIRECTORY_SEPARATOR;
    if (!file_exists($storage_dir)) { mkdir($storage_dir, 0777, true); }
    
    $relative_path = "storage/messages/" . $filename;
    
    if (file_put_contents($storage_dir . $filename, $data->message)) {
        
        // 6. Insert Message into DB
        $query = "INSERT INTO messages (sender_user_id, receiver_user_id, content_file_path) VALUES (:sid, :rid, :path)";
        $stmt = $db->prepare($query);
        
        if($stmt->execute(['sid' => $sender_id, 'rid' => $data->receiver_id, 'path' => $relative_path])) {
            
            // 7. Notification Trigger (Section 10.B)
            $notif_query = "INSERT INTO notifications (user_id, notification_type, related_user_id, content) 
                            VALUES (:target, 'new_message', :sender, :msg)";
            $db->prepare($notif_query)->execute([
                'target' => $data->receiver_id,
                'sender' => $sender_id,
                'msg' => "You have a new message."
            ]);

            echo json_encode(["message" => "Message delivered."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to log message in database."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to save message file."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. receiver_id and message required."]);
}
?>