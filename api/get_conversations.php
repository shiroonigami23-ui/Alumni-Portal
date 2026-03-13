<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_message_schema.php';

function read_message_content(string $relativePath): string
{
    $clean = str_replace(['\\', "\0"], ['/', ''], $relativePath);
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (!is_file($abs)) {
        return '';
    }
    $content = @file_get_contents($abs);
    if ($content === false) {
        return '';
    }
    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        $message = trim((string)($decoded['message'] ?? ''));
        $attachment = is_array($decoded['attachment'] ?? null) ? $decoded['attachment'] : null;
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
    return trim($content);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_message_columns($db);
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
        $lastMessage = !empty($row['deleted_at']) ? 'Message deleted' : read_message_content((string)($row['content_file_path'] ?? ''));
        if ($lastMessage === '') {
            $lastMessage = 'Sent a message';
        }

        $data[] = [
            'conversation_id' => (string)$row['conversation_id'],
            'other_user_id' => (int)$row['other_user_id'],
            'full_name' => (string)$row['full_name'],
            'profile_picture_url' => $row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null,
            'role' => (string)($row['role'] ?? ''),
            'branch' => $row['branch'] ?? null,
            'last_message' => $lastMessage,
            'last_message_at' => $row['last_message_at'],
            'unread_count' => (int)$row['unread_count']
        ];
    }

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
