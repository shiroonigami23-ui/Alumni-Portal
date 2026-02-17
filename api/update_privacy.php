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

if (isset($data->is_private)) {
    $query = "UPDATE profiles SET is_private = :priv WHERE user_id = :uid";
    $stmt = $db->prepare($query);
    
    // Architect Note: Admins stay hidden regardless of this setting
    if ($stmt->execute(['priv' => $data->is_private ? 'true' : 'false', 'uid' => $user_id])) {
        echo json_encode(["message" => "Privacy settings updated."]);
    }
}
?>