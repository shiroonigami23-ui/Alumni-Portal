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

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->target_id) && !empty($data->action)) {
    
    if($data->action === 'ban') {
        // Permanent Ban
        $query = "UPDATE users SET status = 'banned' WHERE user_id = :tid";
        $log_msg = "Permanent ban applied to User ID: " . $data->target_id;
    } else if($data->action === 'kick') {
        // Temporary Suspension (Section 6: Default 5 days if malicious)
        $days = $data->duration ?? 5;
        $expiry = date('Y-m-d H:i:s', strtotime("+$days days"));
        $query = "UPDATE users SET status = 'suspended', suspension_expires_at = :expiry WHERE user_id = :tid";
        $log_msg = "Kicked User ID: " . $data->target_id . " for $days days.";
    }

    $stmt = $db->prepare($query);
    $params = ['tid' => $data->target_id];
    if($data->action === 'kick') $params['expiry'] = $expiry;

    if($stmt->execute($params)) {
        $auth->logAction($db, $admin_id, strtoupper($data->action), $log_msg);
        echo json_encode(["message" => "User " . $data->action . "ed successfully."]);
    }
}
?>