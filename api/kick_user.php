<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$kicker_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

$k_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$k_stmt->execute(['uid' => $kicker_id]);
$kicker = $k_stmt->fetch(PDO::FETCH_ASSOC);

// Section 4.C: Faculty can Kick. Section 4.D: Admin can Kick/Ban.
if ($kicker['role'] === 'faculty' || $kicker['role'] === 'admin') {
    if (!empty($data->target_id) && !empty($data->days)) {
        $expiry = date('Y-m-d H:i:s', strtotime("+" . $data->days . " days"));
        
        $query = "UPDATE users SET status = 'suspended', suspension_expires_at = :exp WHERE user_id = :tid";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute(['exp' => $expiry, 'tid' => $data->target_id])) {
            $auth->logAction($kicker_id, "KICK_USER", "User $data->target_id suspended for $data->days days.");
            echo json_encode(["message" => "User suspended until $expiry."]);
        }
    }
} else {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Only Faculty or Admin can kick users."]);
}
?>