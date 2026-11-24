<?php
/**
 * Public (signed-out) feed for the landing page.
 * Only published, approved posts meant for everyone; never emails; capped and cached.
 * GET ?limit=12 (max 30)
 */
header("Content-Type: application/json; charset=UTF-8");
header('Cache-Control: public, max-age=60');

include_once '../config/Database.php';
include_once __DIR__ . '/_content_store.php';
include_once __DIR__ . '/../helpers/Cache.php';

$limit = max(1, min(30, (int)($_GET['limit'] ?? 12)));
$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['count' => 0, 'posts' => []]);
    exit;
}

try {
    $feed = Cache::remember(Cache::key('public_feed', (string)$limit), 60, function () use ($db, $limit) {
        $stmt = $db->prepare("
            SELECT p.post_id, p.title, p.content_file_path, p.post_type, p.comments_enabled, p.reaction_count, p.comment_count,
                   p.created_at, COALESCE(prof.full_name, 'RJIT member') AS full_name, prof.profile_picture_url
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN profiles prof ON u.user_id = prof.user_id
            WHERE p.status = 'published'
              AND COALESCE(p.moderation_status, 'approved') = 'approved'
              AND COALESCE(p.visibility_scope, 'all') IN ('all', 'public', 'everyone')
              AND u.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $payload = load_content_payload($db, (string)$row['content_file_path']);
            $content = trim((string)($payload['content'] ?? ''));
            if ($content === '' && empty($payload['attachments'])) {
                continue;
            }
            $media = null;
            foreach ((array)($payload['attachments'] ?? []) as $a) {
                if (in_array($a['type'] ?? '', ['image', 'gif'], true) && !empty($a['url'])) {
                    $media = $a['url'];
                    break;
                }
            }
            $out[] = [
                'post_id' => (int)$row['post_id'],
                'title' => $row['title'],
                'content' => $content,
                'author' => $row['full_name'],
                'avatar' => $row['profile_picture_url'],
                'type' => $row['post_type'],
                'media' => $media,
                'comments_allowed' => (bool)$row['comments_enabled'],
                'metrics' => ['reactions' => (int)$row['reaction_count'], 'comments' => (int)$row['comment_count']],
                'created_at' => $row['created_at'],
            ];
        }
        return $out;
    });
    echo json_encode(['count' => count($feed), 'posts' => $feed]);
} catch (Throwable $e) {
    error_log('public_feed: ' . $e->getMessage());
    echo json_encode(['count' => 0, 'posts' => []]);
}
