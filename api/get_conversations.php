<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';
require_once __DIR__ . '/_profile_media.php';
require_once __DIR__ . '/_message_content.php';

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

function read_message_content(PDO $db, ?string $pointer): string
{
    $payload = load_message_payload_record($db, $pointer);
    $message = trim((string)($payload['message'] ?? ''));
    $attachment = is_array($payload['attachment'] ?? null) ? $payload['attachment'] : null;
    if ($message !== '') {
        return $message;
    }
    if ($attachment && !empty($attachment['name'])) {
        return 'Sent an attachment: ' . (string)$attachment['name'];
    }
    if ($attachment && !empty($attachment['url'])) {
        return 'Sent an attachment';
    }
    return '';
}

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_message_columns($db);
    ensure_group_message_schema($db);
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();

    $sql = "
        WITH ranked AS (
            SELECT
                CASE
                    WHEN m.sender_user_id = :uid THEN m.receiver_user_id
                    ELSE m.sender_user_id
                END AS partner_id,
                m.content_file_path,
                m.deleted_at,
                m.created_at,
                m.message_id,
                ROW_NUMBER() OVER (
                    PARTITION BY CASE
                        WHEN m.sender_user_id = :uid THEN m.receiver_user_id
                        ELSE m.sender_user_id
                    END
                    ORDER BY m.created_at DESC, m.message_id DESC
                ) AS rn
            FROM messages m
            WHERE m.sender_user_id = :uid OR m.receiver_user_id = :uid
        ),
        unread AS (
            SELECT m.sender_user_id AS partner_id, COUNT(*)::int AS unread_count
            FROM messages m
            WHERE m.receiver_user_id = :uid
              AND m.read_at IS NULL
            GROUP BY m.sender_user_id
        )
        SELECT
            r.partner_id AS other_user_id,
            r.partner_id AS conversation_id,
            r.content_file_path,
            r.deleted_at,
            r.created_at AS last_message_at,
            COALESCE(u.unread_count, 0) AS unread_count,
            COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(usr.email, '@', 1)) AS full_name,
            p.profile_picture_url,
            usr.role,
            p.branch
        FROM ranked r
        JOIN users usr ON usr.user_id = r.partner_id
        LEFT JOIN profiles p ON p.user_id = r.partner_id
        LEFT JOIN unread u ON u.partner_id = r.partner_id
        WHERE r.rn = 1
        ORDER BY r.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $lastMessage = !empty($row['deleted_at']) ? 'Message deleted' : read_message_content($db, (string)($row['content_file_path'] ?? ''));
        if ($lastMessage === '') {
            $lastMessage = 'Sent a message';
        }

        $data[] = [
            'conversation_id' => (string)$row['conversation_id'],
            'other_user_id' => (int)$row['other_user_id'],
            'full_name' => (string)$row['full_name'],
            'profile_picture_url' => resolve_profile_media_url($db, (int)$row['conversation_id'], $row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null, 'profile_picture_url', 'profile_avatar'),
            'role' => (string)($row['role'] ?? ''),
            'branch' => $row['branch'] ?? null,
            'last_message' => $lastMessage,
            'last_message_at' => $row['last_message_at'],
            'unread_count' => (int)$row['unread_count'],
            'is_group' => false
        ];
    }

    $groupStmt = $db->prepare("
        SELECT
            g.group_id,
            g.title,
            g.updated_at,
            g.mentor_user_id,
            g.admin_user_id,
            COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS mentor_name,
            p.profile_picture_url AS mentor_avatar,
            gm.member_role AS current_member_role,
            member_counts.member_count,
            COALESCE(group_unread.unread_count, 0) AS unread_count,
            last_msg.message_id,
            last_msg.content_file_path,
            last_msg.deleted_at,
            last_msg.created_at AS last_message_at
        FROM mentorship_group_members gm
        JOIN mentorship_groups g ON g.group_id = gm.group_id
        JOIN users u ON u.user_id = g.mentor_user_id
        LEFT JOIN profiles p ON p.user_id = g.mentor_user_id
        LEFT JOIN LATERAL (
            SELECT COUNT(*)::int AS member_count
            FROM mentorship_group_members mgm
            WHERE mgm.group_id = g.group_id
        ) member_counts ON TRUE
        LEFT JOIN mentorship_group_message_reads gr
          ON gr.group_id = g.group_id
         AND gr.user_id = :uid
        LEFT JOIN LATERAL (
            SELECT mgm.message_id, mgm.content_file_path, mgm.deleted_at, mgm.created_at
            FROM mentorship_group_messages mgm
            WHERE mgm.group_id = g.group_id
            ORDER BY mgm.created_at DESC, mgm.message_id DESC
            LIMIT 1
        ) last_msg ON TRUE
        LEFT JOIN LATERAL (
            SELECT COUNT(*)::int AS unread_count
            FROM mentorship_group_messages mgm
            WHERE mgm.group_id = g.group_id
              AND mgm.sender_user_id <> :uid
              AND mgm.deleted_at IS NULL
              AND mgm.message_id > COALESCE(gr.last_read_message_id, 0)
        ) group_unread ON TRUE
        WHERE gm.user_id = :uid
        ORDER BY COALESCE(last_msg.created_at, g.updated_at, g.created_at) DESC
    ");
    $groupStmt->execute([':uid' => $userId]);
    $groups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($groups as $group) {
        $lastMessage = !empty($group['deleted_at']) ? 'Message deleted' : read_message_content($db, (string)($group['content_file_path'] ?? ''));
        if ($lastMessage === '') {
            $lastMessage = 'Mentor group is ready';
        }
        $data[] = [
            'conversation_id' => 'group:' . (string)$group['group_id'],
            'other_user_id' => null,
            'full_name' => (string)($group['title'] ?: 'Mentor Group'),
            'profile_picture_url' => resolve_profile_media_url($db, (int)$group['mentor_user_id'], $group['mentor_avatar'] ? str_replace('\\', '/', (string)$group['mentor_avatar']) : null, 'profile_picture_url', 'profile_avatar'),
            'role' => 'mentor_group',
            'branch' => (string)($group['mentor_name'] ? ('Led by ' . $group['mentor_name']) : 'Mentor group'),
            'last_message' => $lastMessage,
            'last_message_at' => $group['last_message_at'] ?: $group['updated_at'],
            'unread_count' => (int)$group['unread_count'],
            'is_group' => true,
            'group_id' => (int)$group['group_id'],
            'group_admin_user_id' => (int)($group['admin_user_id'] ?? 0),
            'member_role' => (string)($group['current_member_role'] ?? 'member'),
            'member_count' => (int)($group['member_count'] ?? 0),
            'group_badge' => 'Mentor Group'
        ];
    }

    usort($data, static function (array $a, array $b): int {
        return strtotime((string)($b['last_message_at'] ?? '1970-01-01')) <=> strtotime((string)($a['last_message_at'] ?? '1970-01-01'));
    });

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load conversations',
        'error' => $e->getMessage()
    ]);
}
