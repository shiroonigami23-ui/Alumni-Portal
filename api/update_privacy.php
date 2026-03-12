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

$roleStmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid LIMIT 1");
$roleStmt->execute(['uid' => $user_id]);
$userRole = strtolower((string)($roleStmt->fetchColumn() ?: ''));

$requestedPrivate = (bool)$data->is_private;
if ($userRole === 'student') {
    // Students are always public by policy.
    $requestedPrivate = false;
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
    'priv' => $requestedPrivate ? 'true' : 'false'
])) {
    if ($userRole === 'student') {
        echo json_encode([
            "success" => true,
            "message" => "Students are always public. Privacy remains Public.",
            "data" => ["is_private" => false, "enforced_public" => true]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Privacy settings updated.",
            "data" => ["is_private" => $requestedPrivate]
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to update privacy settings."]);
}
?>
