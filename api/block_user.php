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

if (!empty($data->target_id)) {
    // 1. Get Roles
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $my_role = $stmt->fetchColumn();

    $stmt->execute(['uid' => $data->target_id]);
    $target_role = $stmt->fetchColumn();

    // 2. Hierarchy Check (Blueprint Section 4.A/B/D)
    if ($target_role === 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Admin is above all. You cannot block the Architect."]);
        exit();
    }

    if ($my_role === 'student' && $target_role === 'faculty') {
        http_response_code(403);
        echo json_encode(["message" => "Students cannot block Faculty members."]);
        exit();
    }

    // 3. Insert Block using correct column names
    try {
        $query = "INSERT INTO blocks (blocker_user_id, blocked_user_id) VALUES (:bid, :tid)";
        $db->prepare($query)->execute(['bid' => $user_id, 'tid' => $data->target_id]);
        echo json_encode(["message" => "User blocked successfully."]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["message" => "User already blocked or database error."]);
    }
}
?>