<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (empty($data->post_id)) {
    http_response_code(400);
    echo json_encode(["message" => "post_id required."]);
    exit();
}

// 1. Get Requester Role (The Moderator)
$mod_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
$mod_stmt->execute(['id' => $user_id]);
$mod = $mod_stmt->fetch(PDO::FETCH_ASSOC);

// 2. Get Post Owner Role
$post_stmt = $db->prepare("
    SELECT p.user_id as owner_id, u.role as owner_role, p.content_file_path 
    FROM posts p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.post_id = :pid
");
$post_stmt->execute(['pid' => $data->post_id]);
$post = $post_stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    echo json_encode(["message" => "Post not found."]);
    exit();
}

$can_delete = false;

// 3. CORRECTED ARCHITECT PERMISSION LOGIC
if ($user_id == $post['owner_id']) {
    // Self-delete
    $can_delete = true;
} elseif ($mod['role'] === 'admin') {
    // Admin: Master access
    $can_delete = true;
} elseif ($mod['role'] === 'faculty') {
    // Faculty Rule: Can delete Students AND Alumni, but NOT fellow Faculty
    if ($post['owner_role'] === 'student' || $post['owner_role'] === 'alumni') {
        $can_delete = true;
    }
}

// 4. Execution
if ($can_delete) {
    $del = $db->prepare("DELETE FROM posts WHERE post_id = :pid");
    if ($del->execute(['pid' => $data->post_id])) {
        // Purge physical file
        $file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $post['content_file_path']);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        echo json_encode(["message" => "Post moderated successfully per blueprint hierarchy."]);
    }
} else {
    http_response_code(403);
    echo json_encode(["message" => "Permission denied. Faculty cannot delete fellow Faculty posts."]);
}
?>