<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_message_schema.php';

function clear_missing_local_asset(?string $path): string
{
    $path = trim(str_replace('\\', '/', (string)$path));
    if ($path === '' || stripos($path, 'data:image/') === 0) {
        return $path;
    }
    if (preg_match('#\.php(?:$|\?)#i', $path)) {
        return $path;
    }
    if (preg_match('#^https?://#i', $path)) {
        $parsedPath = (string)(parse_url($path, PHP_URL_PATH) ?? '');
        if ($parsedPath === '' || preg_match('#\.php$#i', $parsedPath)) {
            return $path;
        }
        $path = ltrim(str_replace('\\', '/', $parsedPath), '/');
    }
    $path = ltrim($path, '/');
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    return is_file($abs) ? $path : '';
}

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
            SELECT gm.member_role
            FROM mentorship_group_members gm
            WHERE group_id = :gid AND user_id = :uid
            LIMIT 1
        ");
        $membershipCheck->execute([
            ':gid' => $groupId,
            ':uid' => $user_id
        ]);
        $currentMemberRole = $membershipCheck->fetchColumn();
        if (!$currentMemberRole) {
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

        $metaStmt = $db->prepare("
            SELECT
                g.group_id,
                g.title,
                g.admin_user_id,
                COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS mentor_name,
                p.profile_picture_url AS mentor_avatar
            FROM mentorship_groups g
            JOIN users u ON u.user_id = g.mentor_user_id
            LEFT JOIN profiles p ON p.user_id = g.mentor_user_id
            WHERE g.group_id = :gid
            LIMIT 1
        ");
        $metaStmt->execute([':gid' => $groupId]);
        $groupMeta = $metaStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $membersStmt = $db->prepare("
            SELECT
                gm.user_id,
                gm.member_role,
                COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name,
                u.role,
                p.profile_picture_url
            FROM mentorship_group_members gm
            JOIN users u ON u.user_id = gm.user_id
            LEFT JOIN profiles p ON p.user_id = gm.user_id
            WHERE gm.group_id = :gid
            ORDER BY CASE WHEN gm.member_role = 'admin' THEN 0 ELSE 1 END, full_name ASC
        ");
        $membersStmt->execute([':gid' => $groupId]);
        $members = array_map(static function (array $row): array {
            return [
                'user_id' => (int)$row['user_id'],
                'member_role' => (string)($row['member_role'] ?? 'member'),
                'full_name' => (string)($row['full_name'] ?? 'Member'),
                'role' => (string)($row['role'] ?? ''),
                'profile_picture_url' => clear_missing_local_asset($row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null),
            ];
        }, $membersStmt->fetchAll(PDO::FETCH_ASSOC));

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
                "sender_profile_picture" => clear_missing_local_asset($msg['sender_profile_picture'] ? str_replace('\\', '/', (string)$msg['sender_profile_picture']) : null)
            ];
        }

        $latestMessageId = 0;
        foreach ($messages as $msg) {
            $latestMessageId = max($latestMessageId, (int)($msg['message_id'] ?? 0));
        }
        if ($latestMessageId > 0) {
            $readStmt = $db->prepare("
                INSERT INTO mentorship_group_message_reads (group_id, user_id, last_read_message_id, updated_at)
                VALUES (:gid, :uid, :last_read_message_id, NOW())
                ON CONFLICT (group_id, user_id)
                DO UPDATE SET
                    last_read_message_id = GREATEST(COALESCE(mentorship_group_message_reads.last_read_message_id, 0), EXCLUDED.last_read_message_id),
                    updated_at = NOW()
            ");
            $readStmt->execute([
                ':gid' => $groupId,
                ':uid' => $user_id,
                ':last_read_message_id' => $latestMessageId
            ]);
        }

        echo json_encode([
            "success" => true,
            "data" => $history,
            "meta" => [
                'is_group' => true,
                'group_id' => $groupId,
                'title' => (string)($groupMeta['title'] ?? 'Mentor Group'),
                'mentor_name' => (string)($groupMeta['mentor_name'] ?? 'Mentor'),
                'mentor_avatar' => clear_missing_local_asset($groupMeta['mentor_avatar'] ? str_replace('\\', '/', (string)$groupMeta['mentor_avatar']) : null),
                'admin_user_id' => (int)($groupMeta['admin_user_id'] ?? 0),
                'current_member_role' => (string)$currentMemberRole,
                'members' => $members
            ]
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
                "sender_profile_picture" => clear_missing_local_asset($msg['sender_profile_picture'] ? str_replace('\\', '/', (string)$msg['sender_profile_picture']) : null)
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
