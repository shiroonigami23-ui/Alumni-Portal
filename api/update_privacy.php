<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = (int)$auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->is_private)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "is_private is required"]);
    exit;
}

$query = "
    INSERT INTO profiles (user_id, full_name, is_private)
    VALUES (:uid, :full_name, :priv)
    ON CONFLICT (user_id) DO UPDATE
    SET is_private = EXCLUDED.is_private,
        updated_at = CURRENT_TIMESTAMP
";
$stmt = $db->prepare($query);

if ($stmt->execute([
    'uid' => $user_id,
    'full_name' => 'User ' . $user_id,
    'priv' => $data->is_private ? 'true' : 'false'
])) {
    echo json_encode(["success" => true, "message" => "Privacy settings updated."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to update privacy settings."]);
}
?>
