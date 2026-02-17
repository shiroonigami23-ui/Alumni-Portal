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

if (isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($ext !== 'pdf') {
        echo json_encode(["message" => "Verification requires PDF format."]);
        exit();
    }

    $filename = "verify_" . $user_id . "_" . time() . ".pdf";
    $path = dirname(__DIR__) . "/storage/verification/" . $filename;

    if (!file_exists(dirname($path))) mkdir(dirname($path), 0777, true);

    if (move_uploaded_file($file['tmp_name'], $path)) {
        // Update user status to pending_verification (Section 2.B)
        $db->prepare("UPDATE users SET status = 'pending_verification' WHERE user_id = :uid")
           ->execute(['uid' => $user_id]);
        
        echo json_encode(["message" => "Document uploaded. Waiting for Admin approval."]);
    }
}
?>