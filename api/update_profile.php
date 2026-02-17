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

// 1. Check if profile exists
$check = $db->prepare("SELECT profile_id FROM profiles WHERE user_id = :uid");
$check->execute(['uid' => $user_id]);

if ($check->rowCount() > 0) {
    // UPDATE existing
    $query = "UPDATE profiles SET 
              full_name = :name, 
              bio = :bio, 
              skills = :skills, 
              tech_stack = :stack,
              updated_at = NOW() 
              WHERE user_id = :uid";
} else {
    // CREATE new (Fixes the missing profile issue)
    $query = "INSERT INTO profiles (user_id, full_name, bio, skills, tech_stack) 
              VALUES (:uid, :name, :bio, :skills, :stack)";
}

$stmt = $db->prepare($query);

// Sanitize inputs
$name = $data->full_name ?? "Alumni User";
$bio = $data->bio ?? "";
$skills = $data->skills ?? "";
$stack = $data->tech_stack ?? "";

if ($stmt->execute([
    'uid' => $user_id,
    'name' => $name,
    'bio' => $bio,
    'skills' => $skills,
    'stack' => $stack
])) {
    echo json_encode(["message" => "Profile updated successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to update profile."]);
}
?>