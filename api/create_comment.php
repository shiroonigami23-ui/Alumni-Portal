<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_asset_store.php';
include_once __DIR__ . '/_content_store.php';
include_once __DIR__ . '/_moderation_schema.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $isMultipart = !empty($_FILES) || !empty($_POST);

    $post_id = null;
    $content = '';
    $gif_url = '';
    $parent_comment_id = null;

    if ($isMultipart) {
        $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;
        $content = trim((string)($_POST['content'] ?? ''));
        $gif_url = trim((string)($_POST['gif_url'] ?? ''));
        $parent_comment_id = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
            ? (int)$_POST['parent_comment_id']
            : null;
    } else {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $post_id = isset($data['post_id']) ? (int)$data['post_id'] : null;
        $content = trim((string)($data['content'] ?? ''));
        $gif_url = trim((string)($data['gif_url'] ?? ''));
        $parent_comment_id = isset($data['parent_comment_id']) && $data['parent_comment_id'] !== ''
            ? (int)$data['parent_comment_id']
            : null;
    }

    if (!$post_id || ($content === '' && empty($_FILES) && $gif_url === '')) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "post_id and any of content/image/file/gif are required."]);
        exit;
    }

    $permStmt = $db->prepare("SELECT role, can_post FROM users WHERE user_id = :uid LIMIT 1");
    $permStmt->execute([':uid' => $user_id]);
    $userPerms = $permStmt->fetch(PDO::FETCH_ASSOC) ?: ['role' => '', 'can_post' => false];
    $isStudent = (($userPerms['role'] ?? '') === 'student');
    moderation_assert_posting_allowed($db, (int)$user_id, 'Commenting is currently restricted for this account.');
    if ($isStudent && !empty($_FILES)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Students can reply/comment with text and GIF only. File/image uploads are disabled."
        ]);
        exit;
    }

    $uploads = [];

    $saveFile = function (array $file, string $type) use ($db, $user_id, &$uploads) {
        $stored = store_uploaded_asset($db, (int)$user_id, $file, $type, 'comment');
        if ($stored) {
            $uploads[] = $stored;
        }
    };

    if (!empty($_FILES['image'])) {
        $saveFile($_FILES['image'], 'image');
    }
    if (!empty($_FILES['file'])) {
        $saveFile($_FILES['file'], 'file');
    }
    if (!empty($_FILES['images'])) {
        $f = $_FILES['images'];
        if (is_array($f['name'] ?? null)) {
            for ($i = 0; $i < count($f['name']); $i++) {
                $saveFile([
                    'name' => $f['name'][$i],
                    'tmp_name' => $f['tmp_name'][$i] ?? '',
                    'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE
                ], 'image');
            }
        }
    }
    if (!empty($_FILES['files'])) {
        $f = $_FILES['files'];
        if (is_array($f['name'] ?? null)) {
            for ($i = 0; $i < count($f['name']); $i++) {
                $saveFile([
                    'name' => $f['name'][$i],
                    'tmp_name' => $f['tmp_name'][$i] ?? '',
                    'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE
                ], 'file');
            }
        }
    }

    if ($gif_url !== '') {
        $uploads[] = [
            'type' => 'gif',
            'url' => $gif_url,
            'name' => 'GIF'
        ];
    }

    $payload = [
        'content' => $content,
        'attachments' => $uploads
    ];

    $file_path = store_content_payload($db, (int)$user_id, $payload, 'comment');

    $depth_level = 0;
    if ($parent_comment_id !== null && $parent_comment_id > 0) {
        $parentStmt = $db->prepare("SELECT depth_level FROM comments WHERE comment_id = :cid AND post_id = :pid");
        $parentStmt->execute([':cid' => $parent_comment_id, ':pid' => $post_id]);
        $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$parent) {
            http_response_code(404);
            echo json_encode(["success" => false, "status" => "error", "message" => "Parent comment not found."]);
            exit;
        }
        $depth_level = min(((int)$parent['depth_level']) + 1, 5);
    }

    $created = null;
    $inserted = false;
    try {
        $query = "INSERT INTO comments (post_id, user_id, content_file_path, parent_comment_id, depth_level)
                  VALUES (:pid, :uid, :path, :parent_id, :depth)
                  RETURNING comment_id, created_at";
        $stmt = $db->prepare($query);
        $inserted = $stmt->execute([
            'pid' => $post_id,
            'uid' => $user_id,
            'path' => $file_path,
            'parent_id' => $parent_comment_id,
            'depth' => $depth_level
        ]);
        if ($inserted) {
            $created = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $_ignored) {
        $legacyStmt = $db->prepare("INSERT INTO comments (post_id, user_id, content_file_path) VALUES (:pid, :uid, :path) RETURNING comment_id, created_at");
        $inserted = $legacyStmt->execute([
            'pid' => $post_id,
            'uid' => $user_id,
            'path' => $file_path
        ]);
        if ($inserted) {
            $created = $legacyStmt->fetch(PDO::FETCH_ASSOC);
            $parent_comment_id = null;
            $depth_level = 0;
        }
    }

    if ($inserted) {
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Comment added successfully.",
            "data" => [
                "id" => (int)($created['comment_id'] ?? 0),
                "post_id" => (int)$post_id,
                "parent_comment_id" => $parent_comment_id,
                "depth_level" => (int)$depth_level,
                "content" => $content,
                "attachments" => $uploads,
                "created_at" => $created['created_at'] ?? date('c')
            ]
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "Failed to add comment."]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}
