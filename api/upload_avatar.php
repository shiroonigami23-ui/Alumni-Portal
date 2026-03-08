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
        echo json_encode(["success" => false, "status" => "error", "message" => "Invalid format."]);
        exit();
    }

    if (($file['size'] ?? 0) > (2 * 1024 * 1024)) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "File too large. Max 2MB."]);
        exit();
    }

    // 1. Fetch old avatar path to delete it
    $stmt = $db->prepare("SELECT profile_picture_url FROM profiles WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $old_avatar = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Setup paths
    $filename = "avatar_" . $user_id . "_" . bin2hex(random_bytes(4)) . "_" . time() . "." . $extension;
    $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "profiles" . DIRECTORY_SEPARATOR;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
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
            'url' => $db_url
        ]);

        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Avatar updated.",
            "url" => $db_url,
            "avatar_url" => $db_url
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "status" => "error", "message" => "Upload failed."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "status" => "error", "message" => "No avatar file received."]);
}
?>
