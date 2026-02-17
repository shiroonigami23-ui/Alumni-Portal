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

// 1. Check if actor is Admin (Blueprint Section 4.D)
$u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$u_stmt->execute(['uid' => $admin_id]);
$user = $u_stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Forbidden. Only Admin can permanently ban users."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->target_id)) {
    try {
        $db->beginTransaction();

        // 2. Set status to 'banned'
        $query = "UPDATE users SET status = 'banned' WHERE user_id = :tid";
        $db->prepare($query)->execute(['tid' => $data->target_id]);

        // 3. Optional: Logic to blacklist IP/Fingerprint (Blueprint Section 6)
        // For now, we update the user record.
        
        $auth->logAction($admin_id, "PERMANENT_BAN", "Admin banned user " . $data->target_id);
        
        $db->commit();
        echo json_encode(["message" => "User permanently banned."]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Ban failed: " . $e->getMessage()]);
    }
}
?>