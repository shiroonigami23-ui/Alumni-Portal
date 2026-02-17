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

if (isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowed)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid format."]);
        exit();
    }

    // 1. Fetch old avatar path to delete it
    $stmt = $db->prepare("SELECT profile_picture_url FROM profiles WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $old_avatar = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Setup paths
    $filename = "avatar_" . $user_id . "_" . bin2hex(random_bytes(4)) . "_" . time() . "." . $extension;
    $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "profiles" . DIRECTORY_SEPARATOR;
    $upload_path = $upload_dir . $filename;
    $db_url = "storage/profiles/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        
        // 3. Delete old file from disk if it exists
        if (!empty($old_avatar['profile_picture_url'])) {
            $old_file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $old_avatar['profile_picture_url']);
            if (file_exists($old_file_path)) {
                unlink($old_file_path); // Physically remove the old image
            }
        }

        // 4. Update DB
        $update = $db->prepare("UPDATE profiles SET profile_picture_url = :url WHERE user_id = :uid");
        $update->execute(['url' => $db_url, 'uid' => $user_id]);

        echo json_encode(["message" => "Avatar updated. Old file purged.", "url" => $db_url]);
    }
}
?>