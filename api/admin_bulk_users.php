<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$admin_id = $auth->validateRequest();

// Blueprint Role Check (Section 4.D)
$check = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$check->execute(['uid' => $admin_id]);
if($check->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Admin access only."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->user_ids) && !empty($data->action)) {
    $status = ($data->action === 'approve') ? 'active' : 'rejected';
    $placeholders = implode(',', array_fill(0, count($data->user_ids), '?'));

    $query = "UPDATE users SET status = ? WHERE user_id IN ($placeholders) AND status = 'pending'";
    $stmt = $db->prepare($query);
    
    // Merge status and IDs into one array for execution
    $params = array_merge([$status], $data->user_ids);
    
    if($stmt->execute($params)) {
        // Log the bulk action (Section 6)
        $auth->logAction($db, $admin_id, "BULK_" . strtoupper($data->action), "Affected IDs: " . implode(',', $data->user_ids));
        
        echo json_encode(["message" => "Successfully " . $data->action . "d " . $stmt->rowCount() . " users."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Bulk update failed."]);
    }
}
?>