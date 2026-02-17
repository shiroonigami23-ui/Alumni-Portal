<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$admin_id = $auth->validateRequest();

// Blueprint Security Check: Section 4.D
$check = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$check->execute(['uid' => $admin_id]);
if($check->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Only Admin can generate alumni tokens."]);
    exit();
}

$newToken = bin2hex(random_bytes(8)); 

$query = "INSERT INTO alumni_tokens (token, generated_by_admin_id) VALUES (:t, :aid)";
$stmt = $db->prepare($query);

if($stmt->execute(['t' => $newToken, 'aid' => $admin_id])) {
    echo json_encode(["token" => $newToken, "message" => "Token generated for Alumni registration."]);
}
?>