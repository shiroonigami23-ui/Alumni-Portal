<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Job.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// Blueprint Rule: Only Alumni and Faculty can post jobs
$stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!in_array($user['role'], ['alumni', 'faculty', 'admin'])) {
    http_response_code(403);
    echo json_encode(["message" => "Only Alumni or Faculty can post jobs."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$job = new Job($db);

if ($job->create($data, $user_id)) {
    echo json_encode(["message" => "Job opportunity posted successfully!"]);
}
?>