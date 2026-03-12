<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../helpers/FileStorageHelper.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = (int)$auth->validateRequest();

if (!isset($_FILES['cover'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No cover file received."]);
    exit;
}

$file = $_FILES['cover'];
$extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($extension, $allowed, true)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid format."]);
    exit;
}

if (($file['size'] ?? 0) > (5 * 1024 * 1024)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "File too large. Max 5MB."]);
    exit;
}

$stmt = $db->prepare("SELECT cover_photo_url FROM profiles WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "covers" . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = FileStorageHelper::uniqueFileName('cover', (int)$user_id, $extension, 'banner');
$abs = $uploadDir . $filename;
$dbUrl = "storage/covers/" . $filename;

if (!move_uploaded_file($file['tmp_name'], $abs)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Upload failed."]);
    exit;
}

if (!empty($old['cover_photo_url'])) {
    $oldAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$old['cover_photo_url']);
    if (is_file($oldAbs)) {
        @unlink($oldAbs);
    }
}

$upsert = $db->prepare("
    INSERT INTO profiles (user_id, full_name, cover_photo_url)
    VALUES (:uid, :full_name, :url)
    ON CONFLICT (user_id) DO UPDATE
    SET cover_photo_url = EXCLUDED.cover_photo_url,
        updated_at = CURRENT_TIMESTAMP
");
$upsert->execute([
    ':uid' => $user_id,
    ':full_name' => 'User ' . $user_id,
    ':url' => $dbUrl
]);

echo json_encode([
    "success" => true,
    "message" => "Cover updated.",
    "cover_url" => $dbUrl
]);
