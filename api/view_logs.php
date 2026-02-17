<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// Blueprint Security: Only Admin can view logs
$stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$stmt->execute(['uid' => $user_id]);
if ($stmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Admin access only."]);
    exit();
}

$query = "SELECT l.*, u.email FROM activity_logs l 
          LEFT JOIN users u ON l.user_id = u.user_id 
          ORDER BY l.created_at DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>