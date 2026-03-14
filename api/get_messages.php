<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_message_schema.php';

$database = new Database();
$db = $database->getConnection();
ensure_message_columns($db);
ensure_group_message_schema($db);
$auth = new Auth($db);

$user_id = $auth->validateRequest();

function read_message_payload(string $relativePath): array
{
    $clean = str_replace(['\\', "\0"], ['/', ''], $relativePath);
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (!is_file($fullPath)) {
        return [
            'message' => '[Content Missing]',
            'attachment' => null
        ];
    }
    $content = @file_get_contents($fullPath);
    if ($content === false) {
        return [
            'message' => '[Content Missing]',
            'attachment' => null
        ];
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        $message = (string)($decoded['message'] ?? '');
        $attachment = is_array($decoded['attachment'] ?? null) ? $decoded['attachment'] : null;
        if ($attachment && !empty($attachment['url'])) {
            $attachment['url'] = str_replace('\\', '/', (string)$attachment['url']);
        }
        return [
            'message' => $message,
            'attachment' => $attachment
        ];
    }

    return [
        'message' => $content,
        'attachment' => null
    ];
}

$conversationId = trim((string)($_GET['conversation_id'] ?? ''));
$isConversationMode = $conversationId !== '';
$isGroupConversation = false;
$contact_id = 0;
$groupId = 0;
if ($isConversationMode) {
    if (strpos($conversationId, 'group:') === 0) {
        $isGroupConversation = true;
        $groupId = (int)substr($conversationId, 6);
    } else {
        $contact_id = (int)$conversationId;
    }
} elseif (!empty($_GET['contact_id'])) {
    $contact_id = (int)$_GET['contact_id'];
}

if (!$isGroupConversation && $contact_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Contact ID required."]);
    exit;
}
if ($isGroupConversation && $groupId <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Group conversation ID required."]);
    exit;
}

try {
    if ($isGroupConversation) {
        $membershipCheck = $db->prepare("
            SELECT 1
            FROM mentorship_group_members
            WHERE group_id = :gid AND user_id = :uid
            LIMIT 1
        ");
        $membershipCheck->execute([
            ':gid' => $groupId,
            ':uid' => $user_id
        ]);
        if (!$membershipCheck->fetchColumn()) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You are not a member of this mentor group."]);
            exit;
        }

        $query = "
            SELECT
                mgm.message_id,
                mgm.group_id,
                mgm.sender_user_id,
                mgm.content_file_path,
                mgm.created_at,
                mgm.edited_at,
                mgm.deleted_at,
                p.profile_picture_url AS sender_profile_picture
            FROM mentorship_group_messages mgm
            LEFT JOIN profiles p ON p.user_id = mgm.sender_user_id
            WHERE mgm.group_id = :gid
            ORDER BY mgm.created_at ASC, mgm.message_id ASC
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([':gid' => $groupId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $history = [];
        foreach ($messages as $msg) {
            $isDeleted = !empty($msg['deleted_at']);
            $parsed = read_message_payload((string)$msg['content_file_path']);
            $attachment = $isDeleted ? null : ($parsed['attachment'] ?? null);
            $history[] = [
                "message_id" => (int)$msg['message_id'],
                "message_scope" => 'group',
                "sender_id" => (int)$msg['sender_user_id'],
                "receiver_id" => null,
                "message" => $isDeleted ? "This message was deleted" : (string)($parsed['message'] ?? ''),
                "attachment" => $attachment,
                "attachment_url" => is_array($attachment) ? ($attachment['url'] ?? null) : null,
                "attachment_type" => is_array($attachment) ? ($attachment['type'] ?? null) : null,
                "attachment_name" => is_array($attachment) ? ($attachment['name'] ?? null) : null,
                "timestamp" => $msg['created_at'],
                "created_at" => $msg['created_at'],
                "read_at" => null,
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
                "is_read" => true,
                "sender_profile_picture" => $msg['sender_profile_picture'] ? str_replace('\\', '/', (string)$msg['sender_profile_picture']) : null
            ];
        }

        echo json_encode([
            "success" => true,
            "data" => $history
        ]);
        exit;
    }

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
        $parsed = read_message_payload((string)$msg['content_file_path']);
        $attachment = $isDeleted ? null : ($parsed['attachment'] ?? null);
        $history[] = [
            "message_id" => (int)$msg['message_id'],
            "message_scope" => 'direct',
            "sender_id" => (int)$msg['sender_user_id'],
            "receiver_id" => (int)$msg['receiver_user_id'],
            "message" => $isDeleted ? "This message was deleted" : (string)($parsed['message'] ?? ''),
            "attachment" => $attachment,
            "attachment_url" => is_array($attachment) ? ($attachment['url'] ?? null) : null,
            "attachment_type" => is_array($attachment) ? ($attachment['type'] ?? null) : null,
            "attachment_name" => is_array($attachment) ? ($attachment['name'] ?? null) : null,
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
