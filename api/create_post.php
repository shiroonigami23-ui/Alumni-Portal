<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Session.php'; // Required for Security
include_once '../middleware/Security.php';
include_once '../helpers/Logger.php'; // Added Logger

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

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->title) && !empty($data->content)) {
    // 3. 3.5NF Architecture: Content to File
    $filename = "post_body_" . $user_id . "_" . time() . ".txt";
    $storage_dir = dirname(__DIR__) . "/storage/posts/";
    if (!file_exists($storage_dir)) {
        mkdir($storage_dir, 0777, true);
    }

    file_put_contents($storage_dir . $filename, $data->content);
    $relative_path = "storage/posts/" . $filename;

    // 4. Database Insertion
    $query = "INSERT INTO posts 
              (user_id, title, content_file_path, post_type, status, thumbnail_url, comments_enabled) 
              VALUES (:uid, :title, :path, :type, :status, :thumb, :comments) 
              RETURNING post_id";

    $stmt = $db->prepare($query);

    $stmt->execute([
        ':uid'      => $user_id,
        ':title'    => $data->title,
        ':path'     => $relative_path,
        ':type'     => $data->post_type ?? 'text',
        ':status'   => 'published',
        ':thumb'    => $data->thumbnail_url ?? null,
        ':comments' => $data->comments_enabled ?? true
    ]);

    $post_id = $stmt->fetch(PDO::FETCH_ASSOC)['post_id'];

    // 5. Activity Logging (Section 13)
    Logger::log($user_id, "CREATE_POST", "Post ID: $post_id | Title: " . $data->title);

    echo json_encode([
        "status" => "success",
        "message" => "Post architected successfully.",
        "post_id" => $post_id
    ]);
}
