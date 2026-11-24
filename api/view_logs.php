<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'admin']);   // who may call this — enforced before anything below
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// Blueprint Security: Only Admin can view logs
$stmt = api_role_stmt($db, $user_id);
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