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

if (isset($_FILES['attachment'])) {
    $file = $_FILES['attachment'];
    $file_name = $file['name'];
    $context = $_POST['context'] ?? 'posts'; 
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // 1. Virus Scan Logic (Blueprint Section 6 & 13)
    // Checking for EICAR test string in name as a mock for malicious content
    $is_malicious = strpos(strtolower($file_name), "eicar") !== false; 
    if ($is_malicious) {
        $auth->logAction($user_id, "MALICIOUS_UPLOAD", "User attempted to upload infected file: $file_name");
        
        // Suspend user for 5 days (Blueprint Section 6)
        $expiry = date("Y-m-d H:i:s", strtotime("+5 days"));
        $suspend_query = "UPDATE users SET status = 'suspended', suspension_expires_at = :exp WHERE user_id = :uid";
        $stmt = $db->prepare($suspend_query);
        $stmt->execute(["exp" => $expiry, "uid" => $user_id]);
        
        http_response_code(403);
        echo json_encode(["message" => "Malicious file detected. Your account is suspended for 5 days."]);
        exit();
    }

    // 2. Extension Validation
    $allowed_extensions = ['pdf', 'docx', 'epub', 'txt', 'jpg', 'png', 'mp3', 'mp4', 'm4a', 'json'];
    if (!in_array($extension, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(["message" => "File type .$extension not allowed."]);
        exit();
    }

    // 3. Contextual Size Limits
    $max_size = ($context === 'comments') ? (2 * 1024 * 1024) : (10 * 1024 * 1024);
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(["message" => "File too large. Max for $context is " . ($max_size/1024/1024) . "MB."]);
        exit();
    }

    // 4. Storage Logic
    $filename = "file_" . $user_id . "_" . bin2hex(random_bytes(4)) . "_" . time() . "." . $extension;
    $sub_folder = in_array($context, ['posts', 'events', 'comments']) ? $context : 'posts';
    if ($sub_folder === 'comments') $sub_folder = "comments/attachments";

    $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . $sub_folder . DIRECTORY_SEPARATOR;
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $upload_path = $upload_dir . $filename;
    $db_url = "storage/" . $sub_folder . "/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode([
            "message" => "File uploaded successfully to $context.",
            "url" => $db_url,
            "type" => $extension
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to move file to storage."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "No file found. Use field 'attachment'."]);
}
?>