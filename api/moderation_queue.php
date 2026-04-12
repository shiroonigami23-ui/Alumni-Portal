<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_content_store.php';
include_once __DIR__ . '/_community_schema.php';

function moderation_queue_respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    moderation_queue_respond(['success' => false, 'message' => 'Database unavailable.'], 503);
}
$auth = new Auth($db);
ensure_community_schema($db);

try {
    $actorId = (int)$auth->validateRequest();
    $actorStmt = $db->prepare("
        SELECT user_id, role, COALESCE(is_moderator, FALSE) AS is_moderator
        FROM users
        WHERE user_id = :uid
        LIMIT 1
    ");
    $actorStmt->execute(['uid' => $actorId]);
    $actor = $actorStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!user_can_moderate_posts($actor)) {
        moderation_queue_respond(['success' => false, 'message' => 'Only admins and moderators can access this queue.'], 403);
    }
} catch (Throwable $e) {
    moderation_queue_respond(['success' => false, 'message' => 'Unauthorized request.'], 401);
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
    try {
        $stmt = $db->query("
            SELECT
                p.post_id,
                p.user_id,
                p.title,
                p.content_file_path,
                p.post_type,
                p.created_at,
                p.visibility_scope,
                u.role AS author_role,
                u.email AS author_email,
                COALESCE(pr.full_name, u.email) AS author_name
            FROM posts p
            JOIN users u ON u.user_id = p.user_id
            LEFT JOIN profiles pr ON pr.user_id = p.user_id
            WHERE p.status = 'published'
              AND COALESCE(p.moderation_status, 'approved') = 'pending'
              AND u.role = 'alumni'
            ORDER BY p.created_at ASC
            LIMIT 200
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $data = array_map(static function (array $row) use ($db): array {
            $payload = load_content_payload($db, (string)($row['content_file_path'] ?? ''));
            return [
                'post_id' => (int)$row['post_id'],
                'author_id' => (int)$row['user_id'],
                'author_name' => (string)($row['author_name'] ?: 'Alumni Member'),
                'author_email' => (string)($row['author_email'] ?? ''),
                'author_role' => (string)($row['author_role'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
                'content' => (string)($payload['content'] ?? ''),
                'attachments' => $payload['attachments'] ?? [],
                'post_type' => (string)($row['post_type'] ?? 'text'),
                'created_at' => $row['created_at'] ?? null,
                'visibility_scope' => (string)($row['visibility_scope'] ?? 'all'),
            ];
        }, $rows);

        moderation_queue_respond([
            'success' => true,
            'status' => 'success',
            'pending_count' => count($data),
            'data' => $data
        ]);
    } catch (Throwable $e) {
        moderation_queue_respond(['success' => false, 'message' => 'Failed to load moderation queue.', 'error' => $e->getMessage()], 500);
    }
}

try {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $postId = (int)($data['post_id'] ?? 0);
    $action = strtolower(trim((string)($data['action'] ?? '')));
    $note = trim((string)($data['note'] ?? ''));
    if ($postId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        moderation_queue_respond(['success' => false, 'message' => 'post_id and valid action are required.'], 400);
    }

    $targetStmt = $db->prepare("
        SELECT p.post_id, p.user_id, COALESCE(p.moderation_status, 'approved') AS moderation_status, u.role AS author_role
        FROM posts p
        JOIN users u ON u.user_id = p.user_id
        WHERE p.post_id = :pid
        LIMIT 1
    ");
    $targetStmt->execute(['pid' => $postId]);
    $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        moderation_queue_respond(['success' => false, 'message' => 'Post not found.'], 404);
    }
    if ((string)$target['author_role'] !== 'alumni') {
        moderation_queue_respond(['success' => false, 'message' => 'Only alumni posts can be moderated through this queue.'], 409);
    }
    if ((string)$target['moderation_status'] !== 'pending') {
        moderation_queue_respond(['success' => false, 'message' => 'This post is not pending review.'], 409);
    }

    $nextStatus = $action === 'approve' ? 'approved' : 'rejected';
    $update = $db->prepare("
        UPDATE posts
        SET moderation_status = :status,
            reviewed_by_user_id = :reviewer,
            reviewed_at = CURRENT_TIMESTAMP,
            review_note = :note
        WHERE post_id = :pid
    ");
    $update->execute([
        'status' => $nextStatus,
        'reviewer' => $actorId,
        'note' => $note !== '' ? $note : null,
        'pid' => $postId,
    ]);

    moderation_queue_respond([
        'success' => true,
        'status' => 'success',
        'message' => $nextStatus === 'approved' ? 'Post approved and published.' : 'Post rejected.',
        'post_id' => $postId,
        'moderation_status' => $nextStatus
    ]);
} catch (Throwable $e) {
    moderation_queue_respond(['success' => false, 'message' => 'Failed to process moderation action.', 'error' => $e->getMessage()], 500);
}
