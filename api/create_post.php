<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Session.php'; // Required for Security
include_once '../middleware/Security.php';
include_once '../helpers/Logger.php'; // Added Logger
include_once __DIR__ . '/_asset_store.php';
include_once __DIR__ . '/_content_store.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// 1. Validate Token
$user_id = $auth->validateRequest();

// 2. Security Check (CSRF)
Security::checkCSRF();

// 2. Blueprint Permission Check (Section 4)
// Check if user has explicit permission to post
$permQuery = "SELECT role, can_post FROM users WHERE user_id = :uid";
$permStmt = $db->prepare($permQuery);
$permStmt->execute([':uid' => $user_id]);
$userPerms = $permStmt->fetch(PDO::FETCH_ASSOC);

// Students must have can_post = true. Faculty/Admin bypass this.
if ($userPerms['role'] === 'student' && !$userPerms['can_post']) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Permission denied: You are not authorized to post."]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw);

// Support both JSON and multipart/form-data payloads
$title = '';
$content = '';
$post_type = 'text';
$thumbnail_url = null;
$comments_enabled = true;
$pin_post = false; // Pinning is handled only on existing posts via pin_post.php
$gif_url = '';
$attachments = [];

if (is_array($_POST) && !empty($_POST)) {
    $content = trim((string)($_POST['content'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));
    $post_type = trim((string)($_POST['post_type'] ?? 'text'));
    $thumbnail_url = isset($_POST['thumbnail_url']) ? trim((string)$_POST['thumbnail_url']) : null;
    if (isset($_POST['allow_comments'])) {
        $comments_enabled = ($_POST['allow_comments'] === '1' || $_POST['allow_comments'] === 'true');
    }
    $gif_url = trim((string)($_POST['gif_url'] ?? ''));
} else {
    $content = trim((string)($data->content ?? ''));
    $title = trim((string)($data->title ?? ''));
    $post_type = trim((string)($data->post_type ?? 'text'));
    $thumbnail_url = isset($data->thumbnail_url) ? trim((string)$data->thumbnail_url) : null;
    if (isset($data->comments_enabled)) {
        $comments_enabled = (bool)$data->comments_enabled;
    }
    $gif_url = trim((string)($data->gif_url ?? ''));
}

// Collect uploaded media/files using durable DB-backed storage.
$saveUpload = function(array $file, string $kind) use ($db, $user_id, &$attachments) {
    $stored = store_uploaded_asset($db, (int)$user_id, $file, $kind === 'image' ? 'image' : 'file', 'post');
    if ($stored) {
        $attachments[] = $stored;
    }
};

// Supports both single and multi-file inputs
if (!empty($_FILES['image'])) {
    $saveUpload($_FILES['image'], 'image');
}
if (!empty($_FILES['file'])) {
    $saveUpload($_FILES['file'], 'file');
}
if (!empty($_FILES['images'])) {
    $f = $_FILES['images'];
    if (is_array($f['name'] ?? null)) {
        for ($i = 0; $i < count($f['name']); $i++) {
            $saveUpload([
                'name' => $f['name'][$i],
                'type' => $f['type'][$i] ?? '',
                'tmp_name' => $f['tmp_name'][$i] ?? '',
                'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $f['size'][$i] ?? 0
            ], 'image');
        }
    }
}
if (!empty($_FILES['files'])) {
    $f = $_FILES['files'];
    if (is_array($f['name'] ?? null)) {
        for ($i = 0; $i < count($f['name']); $i++) {
            $saveUpload([
                'name' => $f['name'][$i],
                'type' => $f['type'][$i] ?? '',
                'tmp_name' => $f['tmp_name'][$i] ?? '',
                'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $f['size'][$i] ?? 0
            ], 'file');
        }
    }
}

if ($gif_url !== '') {
    $attachments[] = [
        'type' => 'gif',
        'url' => $gif_url,
        'name' => 'GIF'
    ];
}

if ($title === '' && $content !== '') {
    $title = mb_substr($content, 0, 80);
}
if ($title === '' && $content === '' && !empty($attachments)) {
    $title = 'Media post';
}

if (!empty($content) || !empty($attachments)) {
    $payload = [
        'content' => $content,
        'attachments' => $attachments
    ];
    $relative_path = store_content_payload($db, (int)$user_id, $payload, 'post');

    // 4. Database Insertion
    $query = "INSERT INTO posts 
              (user_id, title, content_file_path, post_type, status, thumbnail_url, comments_enabled, is_pinned) 
              VALUES (:uid, :title, :path, :type, :status, :thumb, :comments, :pinned) 
              RETURNING post_id";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':uid', (int)$user_id, PDO::PARAM_INT);
    $stmt->bindValue(':title', (string)$title, PDO::PARAM_STR);
    $stmt->bindValue(':path', (string)$relative_path, PDO::PARAM_STR);
    $stmt->bindValue(':type', (string)($post_type ?: 'text'), PDO::PARAM_STR);
    $stmt->bindValue(':status', 'published', PDO::PARAM_STR);
    if ($thumbnail_url === null || $thumbnail_url === '') {
        $stmt->bindValue(':thumb', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':thumb', (string)$thumbnail_url, PDO::PARAM_STR);
    }
    $stmt->bindValue(':comments', (bool)$comments_enabled, PDO::PARAM_BOOL);
    $stmt->bindValue(':pinned', (bool)$pin_post, PDO::PARAM_BOOL);

    try {
        $stmt->execute();
        $createdRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $post_id = (int)($createdRow['post_id'] ?? 0);
        if ($post_id <= 0) {
            throw new RuntimeException('Post creation returned an invalid post id.');
        }
    } catch (Throwable $dbError) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Failed to create post."
        ]);
        exit;
    }

    // 5. Activity Logging (Section 13)
    Logger::log($user_id, "CREATE_POST", "Post ID: $post_id | Title: " . $title);

    // Notify followers when a user posts (best-effort, should not block post creation).
    try {
        $posterNameStmt = $db->prepare("
            SELECT COALESCE(NULLIF(TRIM(full_name), ''), 'Someone') AS name
            FROM profiles
            WHERE user_id = :uid
            LIMIT 1
        ");
        $posterNameStmt->execute([':uid' => $user_id]);
        $posterName = (string)($posterNameStmt->fetchColumn() ?: 'Someone');

        $notifContent = $posterName . " posted a new update.";
        $notifSqlNewPost = "
            INSERT INTO notifications (user_id, notification_type, related_post_id, related_user_id, content)
            SELECT c.requester_user_id, 'new_post'::notification_type, :post_id, :poster_id, :content
            FROM connections c
            WHERE c.status = 'accepted'
              AND c.addressee_user_id = :poster_id
              AND c.requester_user_id <> :poster_id
        ";
        $notifStmt = $db->prepare($notifSqlNewPost);
        $notifStmt->execute([
            ':post_id' => $post_id,
            ':poster_id' => $user_id,
            ':content' => $notifContent
        ]);
    } catch (Throwable $notifError) {
        try {
            // Fallback to a broadly supported notification type in case enum lacks new_post.
            $fallbackSql = "
                INSERT INTO notifications (user_id, notification_type, related_post_id, related_user_id, content)
                SELECT c.requester_user_id, 'new_comment'::notification_type, :post_id, :poster_id, :content
                FROM connections c
                WHERE c.status = 'accepted'
                  AND c.addressee_user_id = :poster_id
                  AND c.requester_user_id <> :poster_id
            ";
            $fallbackStmt = $db->prepare($fallbackSql);
            $fallbackStmt->execute([
                ':post_id' => $post_id,
                ':poster_id' => $user_id,
                ':content' => 'New post from someone you follow.'
            ]);
        } catch (Throwable $ignored) {
            // Intentionally ignored to keep post creation resilient.
        }
    }

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Post architected successfully.",
        "post_id" => $post_id
    ]);
    exit;
}

http_response_code(400);
echo json_encode([
    "success" => false,
    "status" => "error",
    "message" => "Post content is required."
]);
