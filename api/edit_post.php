<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_content_store.php';
include_once __DIR__ . '/_community_schema.php';

function normalize_typo_signature(string $text): string
{
    $lower = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $normalized = preg_replace('/[\p{P}\p{S}\s]+/u', '', $lower);
    return (string)($normalized ?? '');
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode(["success" => false, "status" => "error", "message" => "Database unavailable."]);
    exit;
}
ensure_community_schema($db);
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];

    $post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
    $content = trim((string)($data['content'] ?? ''));

    if ($post_id <= 0 || $content === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id and content are required."]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT
            post_id,
            user_id,
            title,
            content_file_path,
            moderation_status,
            COALESCE(pending_edit_status, 'none') AS pending_edit_status,
            pending_edit_content_file_path,
            created_at,
            COALESCE(revision_no, 1) AS revision_no
        FROM posts
        WHERE post_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':pid' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "Post not found."]);
        exit;
    }
    if ((int)$post['user_id'] !== (int)$user_id) {
        http_response_code(403);
        echo json_encode(["success" => false, "status" => "error", "message" => "You can edit only your own post."]);
        exit;
    }

    $roleStmt = $db->prepare("SELECT role, can_post, COALESCE(is_moderator, FALSE) AS is_moderator FROM users WHERE user_id = :uid LIMIT 1");
    $roleStmt->execute([':uid' => $user_id]);
    $actor = $roleStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $requiresModeration = post_needs_moderation($actor);

    $filePath = (string)$post['content_file_path'];
    $existingPayload = load_content_payload($db, $filePath);
    $attachments = $existingPayload['attachments'];
    $oldContent = (string)($existingPayload['content'] ?? '');
    $title = mb_substr($content, 0, 80);
    $nextRevisionNo = ((int)($post['revision_no'] ?? 1)) + 1;

    $isApprovedLivePost = ((string)($post['moderation_status'] ?? 'approved') === 'approved');
    $createdAtTs = strtotime((string)($post['created_at'] ?? ''));
    $withinTypoWindow = ($createdAtTs !== false) && ((time() - $createdAtTs) <= 300);
    $typoOnlyChange = normalize_typo_signature($oldContent) === normalize_typo_signature($content);
    $isContentActuallyDifferent = trim($oldContent) !== trim($content);

    if (!$isContentActuallyDifferent) {
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "No content changes detected.",
            "data" => [
                "post_id" => $post_id,
                "content" => $oldContent,
                "edit_mode" => "no_change"
            ]
        ]);
        exit;
    }

    $canApplyImmediately = !$requiresModeration || (!$isApprovedLivePost) || ($withinTypoWindow && $typoOnlyChange);

    if ($canApplyImmediately) {
        $payload = [
            'content' => $content,
            'attachments' => $attachments
        ];
        update_content_payload($db, $filePath, $payload, (int)$user_id, 'post');

        $upd = $db->prepare("
            UPDATE posts
            SET title = :title,
                is_edited = true,
                last_edited_at = NOW(),
                updated_at = NOW(),
                revision_no = :revision_no,
                pending_edit_status = 'none',
                pending_edit_content_file_path = NULL,
                pending_edit_submitted_at = NULL,
                pending_revision_no = NULL
            WHERE post_id = :pid
        ");
        $upd->execute([
            ':title' => $title,
            ':revision_no' => $nextRevisionNo,
            ':pid' => $post_id
        ]);

        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => ($withinTypoWindow && $typoOnlyChange && $requiresModeration && $isApprovedLivePost)
                ? "Typo-only edit applied instantly (5-minute window)."
                : "Post updated.",
            "data" => [
                "post_id" => $post_id,
                "content" => $content,
                "edit_mode" => ($withinTypoWindow && $typoOnlyChange && $requiresModeration && $isApprovedLivePost) ? "applied_typo_window" : "applied_direct",
                "revision_no" => $nextRevisionNo
            ]
        ]);
        exit;
    }

    $payload = [
        'content' => $content,
        'attachments' => $attachments
    ];
    $pendingPath = store_content_payload($db, (int)$user_id, $payload, 'post');

    $upd = $db->prepare("
        UPDATE posts
        SET pending_edit_status = 'pending',
            pending_edit_content_file_path = :pending_path,
            pending_edit_submitted_at = NOW(),
            pending_revision_no = :pending_revision_no,
            updated_at = NOW()
        WHERE post_id = :pid
    ");
    $upd->execute([
        ':pending_path' => $pendingPath,
        ':pending_revision_no' => $nextRevisionNo,
        ':pid' => $post_id
    ]);

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Edit submitted for moderator review. Your current approved version remains visible until approval.",
        "data" => [
            "post_id" => $post_id,
            "content" => $content,
            "edit_mode" => "pending_review",
            "pending_revision_no" => $nextRevisionNo
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}
