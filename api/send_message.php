<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../helpers/FileStorageHelper.php';
include_once __DIR__ . '/_message_schema.php';

$database = new Database();
$db = $database->getConnection();
ensure_message_columns($db);
ensure_group_message_schema($db);
$auth = new Auth($db);

$sender_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

$receiver_id = 0;
$group_id = 0;
if (!empty($data->conversation_id) && is_string($data->conversation_id) && strpos($data->conversation_id, 'group:') === 0) {
    $group_id = (int)substr((string)$data->conversation_id, 6);
}
if (!empty($data->receiver_id)) {
    $receiver_id = (int)$data->receiver_id;
} elseif (!empty($data->conversation_id) && !$group_id) {
    // Compatibility for conversation-based clients where conversation_id == other user id
    $receiver_id = (int)$data->conversation_id;
} elseif (!empty($data->other_user_id)) {
    $receiver_id = (int)$data->other_user_id;
}

$messageText = trim((string)($data->message ?? ''));
$attachmentUrl = trim((string)($data->attachment_url ?? ''));
$attachmentType = trim((string)($data->attachment_type ?? ''));
$attachmentName = trim((string)($data->attachment_name ?? ''));

if (($receiver_id > 0 || $group_id > 0) && ($messageText !== '' || $attachmentUrl !== '')) {
    
    // 1. Get Roles for Hierarchy Logic
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :sid");
    $stmt->execute(['sid' => $sender_id]);
    $sender_role = $stmt->fetchColumn();

    $receiver_role = null;
    if ($receiver_id > 0) {
        $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :rid");
        $stmt->execute(['rid' => $receiver_id]);
        $receiver_role = $stmt->fetchColumn();
        if (!$receiver_role) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Receiver not found."]);
            exit();
        }
    }

    // 2. Admin Bypass (Blueprint Section 4.D)
    // Admin is above all; they skip Block and Privacy checks.
    if ($group_id > 0) {
        $membership = $db->prepare("
            SELECT 1
            FROM mentorship_group_members
            WHERE group_id = :gid AND user_id = :uid
            LIMIT 1
        ");
        $membership->execute([
            'gid' => $group_id,
            'uid' => $sender_id
        ]);
        if (!$membership->fetchColumn()) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You are not a member of this mentor group."]);
            exit();
        }
    } elseif ($sender_role !== 'admin') {
        
        // 3. Block Check (Blueprint Section 18.2 - Mutual Invisibility)
        // Using verified column names: blocker_user_id, blocked_user_id
        $block_check = $db->prepare("SELECT 1 FROM blocks 
                                     WHERE (blocker_user_id = :sid AND blocked_user_id = :rid) 
                                     OR (blocker_user_id = :rid AND blocked_user_id = :sid)");
        $block_check->execute(['sid' => $sender_id, 'rid' => $receiver_id]);
        if ($block_check->fetch()) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Messaging blocked."]);
            exit();
        }

        // 4. Privacy Check (Section 7)
        $p_check = $db->prepare("SELECT is_private FROM profiles WHERE user_id = :rid");
        $p_check->execute(['rid' => $receiver_id]);
        $is_private = $p_check->fetchColumn();

        if ($is_private && $sender_id != $receiver_id) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Cannot message a private profile."]);
            exit();
        }
    }

    // 5. 3.5NF Storage Logic (Message Content to File)
    $filename = FileStorageHelper::uniqueFileName('msg', (int)$sender_id, 'txt', 'chat');
    $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "messages" . DIRECTORY_SEPARATOR;
    if (!file_exists($storage_dir)) { mkdir($storage_dir, 0777, true); }
    
    $relative_path = "storage/messages/" . $filename;
    
    $messagePayload = [
        'message' => $messageText
    ];
    if ($attachmentUrl !== '') {
        $messagePayload['attachment'] = [
            'url' => str_replace('\\', '/', $attachmentUrl),
            'type' => $attachmentType ?: null,
            'name' => $attachmentName ?: null
        ];
    }
    $payloadJson = json_encode($messagePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($payloadJson !== false && file_put_contents($storage_dir . $filename, $payloadJson)) {
        
        // 6. Insert Message into DB
        if ($group_id > 0) {
            $query = "INSERT INTO mentorship_group_messages (group_id, sender_user_id, content_file_path) VALUES (:gid, :sid, :path)";
            $stmt = $db->prepare($query);

            if ($stmt->execute(['gid' => $group_id, 'sid' => $sender_id, 'path' => $relative_path])) {
                $messageId = (int)$db->lastInsertId();
                echo json_encode([
                    "success" => true,
                    "message" => "Message delivered.",
                    "data" => [
                        "message_id" => $messageId,
                        "conversation_id" => 'group:' . (string)$group_id
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to log group message in database."]);
            }
        } else {
            $query = "INSERT INTO messages (sender_user_id, receiver_user_id, content_file_path) VALUES (:sid, :rid, :path)";
            $stmt = $db->prepare($query);

            if($stmt->execute(['sid' => $sender_id, 'rid' => $receiver_id, 'path' => $relative_path])) {
                $messageId = (int)$db->lastInsertId();
                
                // 7. Notification Trigger (Section 10.B)
                $notif_query = "INSERT INTO notifications (user_id, notification_type, related_user_id, content) 
                                VALUES (:target, 'new_message', :sender, :msg)";
                $db->prepare($notif_query)->execute([
                    'target' => $receiver_id,
                    'sender' => $sender_id,
                    'msg' => "You have a new message."
                ]);

                echo json_encode([
                    "success" => true,
                    "message" => "Message delivered.",
                    "data" => [
                        "message_id" => $messageId,
                        "receiver_id" => $receiver_id,
                        "conversation_id" => (string)$receiver_id
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to log message in database."]);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to save message file."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete data. A conversation target and either message or attachment are required."]);
}
?>
