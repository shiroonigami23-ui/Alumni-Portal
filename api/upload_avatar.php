<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_asset_store.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

if (isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowed)) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "Invalid format."]);
        exit();
    }

    if (($file['size'] ?? 0) > (2 * 1024 * 1024)) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "File too large. Max 2MB."]);
        exit();
    }

    // 1. Fetch old avatar path to replace it
    $stmt = $db->prepare("SELECT profile_picture_url FROM profiles WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $old_avatar = $stmt->fetch(PDO::FETCH_ASSOC);
    $stored = store_uploaded_asset($db, (int)$user_id, $file, 'image', 'profile_avatar');
    if (!$stored || empty($stored['url'])) {
        http_response_code(500);
        echo json_encode(["success" => false, "status" => "error", "message" => "Upload failed."]);
        exit();
    }

    if (!empty($old_avatar['profile_picture_url'])) {
        delete_asset_by_url($db, (string)$old_avatar['profile_picture_url']);
        $old_file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$old_avatar['profile_picture_url']);
        if (is_file($old_file_path)) {
            @unlink($old_file_path);
        }
    }

    $insertOrUpdate = $db->prepare("
        INSERT INTO profiles (user_id, full_name, profile_picture_url)
        VALUES (:uid, :full_name, :url)
        ON CONFLICT (user_id) DO UPDATE
        SET profile_picture_url = EXCLUDED.profile_picture_url,
            updated_at = CURRENT_TIMESTAMP
    ");
    $insertOrUpdate->execute([
        'uid' => $user_id,
        'full_name' => 'User ' . $user_id,
        'url' => (string)$stored['url']
    ]);

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Avatar updated.",
        "url" => (string)$stored['url'],
        "avatar_url" => (string)$stored['url']
    ]);
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "status" => "error", "message" => "No avatar file received."]);
}
