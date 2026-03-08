<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_message_schema.php';

$database = new Database();
$db = $database->getConnection();
ensure_message_columns($db);
$auth = new Auth($db);

$user_id = $auth->validateRequest();

function read_message_body(string $relativePath): string
{
    $clean = str_replace(['\\', "\0"], ['/', ''], $relativePath);
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (!is_file($fullPath)) {
        return '[Content Missing]';
    }
    $content = @file_get_contents($fullPath);
    return $content === false ? '[Content Missing]' : $content;
}

$isConversationMode = !empty($_GET['conversation_id']);
$contact_id = 0;
if ($isConversationMode) {
    $contact_id = (int)$_GET['conversation_id'];
} elseif (!empty($_GET['contact_id'])) {
    $contact_id = (int)$_GET['contact_id'];
}

if ($contact_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Contact ID required."]);
    exit;
}

try {
    // Mark incoming messages as read
    $markRead = $db->prepare("
        UPDATE messages
        SET read_at = NOW()
        WHERE sender_user_id = :cid
          AND receiver_user_id = :uid
          AND read_at IS NULL
    ");
    $markRead->execute([':cid' => $contact_id, ':uid' => $user_id]);

    $query = "
        SELECT m.*, p.profile_picture_url AS sender_profile_picture
        FROM messages m
        LEFT JOIN profiles p ON p.user_id = m.sender_user_id
        WHERE (m.sender_user_id = :uid AND m.receiver_user_id = :cid)
           OR (m.sender_user_id = :cid AND m.receiver_user_id = :uid)
        ORDER BY m.created_at ASC, m.message_id ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([':uid' => $user_id, ':cid' => $contact_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $history = [];
    foreach ($messages as $msg) {
        $isDeleted = !empty($msg['deleted_at']);
        $history[] = [
            "message_id" => (int)$msg['message_id'],
            "sender_id" => (int)$msg['sender_user_id'],
            "receiver_id" => (int)$msg['receiver_user_id'],
            "message" => $isDeleted ? "This message was deleted" : read_message_body((string)$msg['content_file_path']),
            "timestamp" => $msg['created_at'],
            "created_at" => $msg['created_at'],
            "read_at" => $msg['read_at'],
            "edited_at" => $msg['edited_at'] ?? null,
            "deleted_at" => $msg['deleted_at'] ?? null,
            "is_deleted" => $isDeleted,
            "is_edited" => !empty($msg['edited_at']) && !$isDeleted,
            "can_edit" => (
                ((int)$msg['sender_user_id'] === $user_id) &&
                !$isDeleted &&
                !empty($msg['created_at']) &&
                (strtotime((string)$msg['created_at']) >= strtotime('-30 minutes'))
            ),
            "can_delete" => ((int)$msg['sender_user_id'] === $user_id),
            "is_read" => !is_null($msg['read_at']),
            "sender_profile_picture" => $msg['sender_profile_picture'] ? str_replace('\\', '/', (string)$msg['sender_profile_picture']) : null
        ];
    }

    if ($isConversationMode) {
        echo json_encode([
            "success" => true,
            "data" => $history
        ]);
    } else {
        // Legacy response shape
        echo json_encode($history);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to load messages",
        "error" => $e->getMessage()
    ]);
}
?>
