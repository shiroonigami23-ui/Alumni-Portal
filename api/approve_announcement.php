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

// Verify Role (Faculty or Admin)
$u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$u_stmt->execute(['uid' => $admin_id]);
$user = $u_stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] === 'admin' || $user['role'] === 'faculty') {
    if (!empty($data->announcement_id)) {
        $query = "UPDATE announcements SET status = 'published', approved_by_admin_id = :aid, approved_at = NOW() 
                  WHERE announcement_id = :id";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute(['aid' => $admin_id, 'id' => $data->announcement_id])) {
            echo json_encode(["message" => "Announcement approved and is now live."]);
        }
    }
} else {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized approval attempt."]);
}
?>