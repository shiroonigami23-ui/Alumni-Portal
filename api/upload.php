<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../helpers/AWSHelper.php';
include_once '../middleware/Security.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Validate Token
$user_id = $auth->validateRequest();

// CSRF Check (Uploads are state changing)
// Note: Frontend must send X-CSRF-TOKEN header
Security::checkCSRF();

$aws = new AWSHelper();

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];

    if ($fileError === 0) {
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx');

        if (in_array($fileExt, $allowed)) {
            if ($file['size'] < 5000000) { // 5MB
                $newFileName = uniqid('', true) . "." . $fileExt;

                // Construct S3 Key (Folder structure: uploads/user_id/filename)
                $key = "uploads/" . $user_id . "/" . $newFileName;

                // Upload to S3 if configured, else local fallback could be implemented here
                // But for this phase we assume AWS goal
                $s3Url = $aws->uploadFile($key, $fileTmpName);

                if ($s3Url) {
                    echo json_encode(["message" => "Upload successful.", "url" => $s3Url]);
                } else {
                    // Fallback to local if AWS not configured or failed
                    $uploadDir = '../storage/uploads/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $destination = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmpName, $destination)) {
                        // Return local path (relative)
                        $localUrl = "storage/uploads/" . $newFileName;
                        echo json_encode(["message" => "Upload successful (Local).", "url" => $localUrl]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Upload failed."]);
                    }
                }
            } else {
                echo json_encode(["message" => "File too large."]);
            }
        } else {
            echo json_encode(["message" => "Invalid file type."]);
        }
    } else {
        echo json_encode(["message" => "Error uploading file."]);
    }
} else {
    echo json_encode(["message" => "No file provided."]);
}
