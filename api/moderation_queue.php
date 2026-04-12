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
                p.pending_edit_content_file_path,
                COALESCE(p.pending_edit_status, 'none') AS pending_edit_status,
                COALESCE(p.revision_no, 1) AS revision_no,
                p.pending_revision_no,
                p.post_type,
                p.created_at,
                p.visibility_scope,
                p.pending_edit_submitted_at,
                u.role AS author_role,
                u.email AS author_email,
                COALESCE(pr.full_name, u.email) AS author_name
            FROM posts p
            JOIN users u ON u.user_id = p.user_id
            LEFT JOIN profiles pr ON pr.user_id = p.user_id
            WHERE p.status = 'published'
              AND (
                COALESCE(p.moderation_status, 'approved') = 'pending'
                OR COALESCE(p.pending_edit_status, 'none') = 'pending'
              )
              AND u.role = 'alumni'
            ORDER BY COALESCE(p.pending_edit_submitted_at, p.created_at) ASC
            LIMIT 200
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $data = array_map(static function (array $row) use ($db): array {
            $isPendingEdit = ((string)($row['pending_edit_status'] ?? 'none') === 'pending');
            $pendingPayload = $isPendingEdit
                ? load_content_payload($db, (string)($row['pending_edit_content_file_path'] ?? ''))
                : ['content' => '', 'attachments' => []];
            $livePayload = load_content_payload($db, (string)($row['content_file_path'] ?? ''));
            return [
                'post_id' => (int)$row['post_id'],
                'author_id' => (int)$row['user_id'],
                'author_name' => (string)($row['author_name'] ?: 'Alumni Member'),
                'author_email' => (string)($row['author_email'] ?? ''),
                'author_role' => (string)($row['author_role'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
                'queue_item_type' => $isPendingEdit ? 'edit_revision' : 'new_post',
                'content' => $isPendingEdit ? (string)($pendingPayload['content'] ?? '') : (string)($livePayload['content'] ?? ''),
                'attachments' => $isPendingEdit ? ($pendingPayload['attachments'] ?? []) : ($livePayload['attachments'] ?? []),
                'current_live_content' => $isPendingEdit ? (string)($livePayload['content'] ?? '') : '',
                'current_live_attachments' => $isPendingEdit ? ($livePayload['attachments'] ?? []) : [],
                'post_type' => (string)($row['post_type'] ?? 'text'),
                'created_at' => $row['created_at'] ?? null,
                'queued_at' => $row['pending_edit_submitted_at'] ?? $row['created_at'] ?? null,
                'visibility_scope' => (string)($row['visibility_scope'] ?? 'all'),
                'revision_no' => (int)($row['revision_no'] ?? 1),
                'pending_revision_no' => isset($row['pending_revision_no']) ? (int)$row['pending_revision_no'] : null,
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
        SELECT
            p.post_id,
            p.user_id,
            p.title,
            p.content_file_path,
            p.pending_edit_content_file_path,
            COALESCE(p.moderation_status, 'approved') AS moderation_status,
            COALESCE(p.pending_edit_status, 'none') AS pending_edit_status,
            COALESCE(p.revision_no, 1) AS revision_no,
            p.pending_revision_no,
            u.role AS author_role
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
    $isPendingNewPost = ((string)$target['moderation_status'] === 'pending');
    $isPendingEdit = ((string)$target['pending_edit_status'] === 'pending');
    if (!$isPendingNewPost && !$isPendingEdit) {
        moderation_queue_respond(['success' => false, 'message' => 'This post is not pending review.'], 409);
    }

    if ($isPendingEdit) {
        if ($action === 'approve') {
            $pendingPath = (string)($target['pending_edit_content_file_path'] ?? '');
            if ($pendingPath === '') {
                moderation_queue_respond(['success' => false, 'message' => 'Pending revision payload is missing.'], 409);
            }
            $pendingPayload = load_content_payload($db, $pendingPath);
            $nextTitle = mb_substr((string)($pendingPayload['content'] ?? ''), 0, 80);
            $approvedRevision = (int)($target['pending_revision_no'] ?? ((int)$target['revision_no'] + 1));

            $update = $db->prepare("
                UPDATE posts
                SET title = :title,
                    previous_content_file_path = content_file_path,
                    previous_revision_no = revision_no,
                    content_file_path = :pending_path,
                    revision_no = :revision_no,
                    pending_revision_no = NULL,
                    pending_edit_status = 'none',
                    pending_edit_content_file_path = NULL,
                    pending_edit_submitted_at = NULL,
                    moderation_status = 'approved',
                    is_edited = true,
                    last_edited_at = CURRENT_TIMESTAMP,
                    reviewed_by_user_id = :reviewer,
                    reviewed_at = CURRENT_TIMESTAMP,
                    review_note = :note,
                    updated_at = CURRENT_TIMESTAMP
                WHERE post_id = :pid
            ");
            $update->execute([
                'title' => $nextTitle,
                'pending_path' => $pendingPath,
                'revision_no' => $approvedRevision,
                'reviewer' => $actorId,
                'note' => $note !== '' ? $note : null,
                'pid' => $postId,
            ]);
            moderation_queue_respond([
                'success' => true,
                'status' => 'success',
                'message' => 'Post edit approved. New version is now live.',
                'post_id' => $postId,
                'moderation_status' => 'approved',
                'queue_item_type' => 'edit_revision'
            ]);
        }

        $reject = $db->prepare("
            UPDATE posts
            SET pending_edit_status = 'rejected',
                pending_edit_content_file_path = NULL,
                pending_edit_submitted_at = NULL,
                pending_revision_no = NULL,
                reviewed_by_user_id = :reviewer,
                reviewed_at = CURRENT_TIMESTAMP,
                review_note = :note,
                updated_at = CURRENT_TIMESTAMP
            WHERE post_id = :pid
        ");
        $reject->execute([
            'reviewer' => $actorId,
            'note' => $note !== '' ? $note : null,
            'pid' => $postId,
        ]);
        moderation_queue_respond([
            'success' => true,
            'status' => 'success',
            'message' => 'Post edit rejected. Current live version remains unchanged.',
            'post_id' => $postId,
            'moderation_status' => (string)$target['moderation_status'],
            'queue_item_type' => 'edit_revision'
        ]);
    }

    $nextStatus = $action === 'approve' ? 'approved' : 'rejected';
    $newPostReview = $db->prepare("
        UPDATE posts
        SET moderation_status = :status,
            reviewed_by_user_id = :reviewer,
            reviewed_at = CURRENT_TIMESTAMP,
            review_note = :note
        WHERE post_id = :pid
    ");
    $newPostReview->execute([
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
        'moderation_status' => $nextStatus,
        'queue_item_type' => 'new_post'
    ]);
} catch (Throwable $e) {
    moderation_queue_respond(['success' => false, 'message' => 'Failed to process moderation action.', 'error' => $e->getMessage()], 500);
}
