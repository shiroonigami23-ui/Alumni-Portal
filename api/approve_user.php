<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$requestor_id = $auth->validateRequest();

// 1. Check Requestor's Role
$stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
$stmt->execute(['id' => $requestor_id]);
$requestor = $stmt->fetch(PDO::FETCH_ASSOC);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->target_user_id) && !empty($data->action)) {
    // 2. Identify the target user's role and current status
    $stmt = $db->prepare("SELECT role, status FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $data->target_user_id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        http_response_code(404);
        echo json_encode(["message" => "Target user not found."]);
        exit();
    }

    // 3. Status Guard: Faculty cannot lift a Permanent Ban (Section 6)
    if ($target['status'] === 'banned' && $requestor['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Account is permanently banned. Only Admin can lift a ban."]);
        exit();
    }

    $can_approve = false;

    // Admin can approve/restore anyone (Section 4.D)
    if ($requestor['role'] === 'admin') {
        $can_approve = true;
    } 
    // Faculty can ONLY approve alumni (Section 4.C)
    elseif ($requestor['role'] === 'faculty' && $target['role'] === 'alumni') {
        $can_approve = true;
    }

    if ($can_approve) {
        $new_status = ($data->action === 'approve') ? 'active' : 'rejected';
        
        // If restoring from suspension/ban, we also clear the expiry timestamp
        $update = $db->prepare("UPDATE users SET status = :status, suspension_expires_at = NULL WHERE user_id = :tid");
        
        if ($update->execute(['status' => $new_status, 'tid' => $data->target_user_id])) {
            // Log the action for Admin Audit (Section 13)
            $auth->logAction($requestor_id, "USER_APPROVAL", "Target ID: " . $data->target_user_id . " set to " . $new_status);
            
            echo json_encode(["message" => "User status updated to $new_status by " . $requestor['role']]);
        }
    } else {
        http_response_code(403);
        // Specific error message for Faculty trying to approve non-alumni (like Students)
        $msg = ($requestor['role'] === 'faculty') ? "Unauthorized. Faculty can only approve Alumni." : "Forbidden.";
        echo json_encode(["message" => $msg]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. target_user_id and action required."]);
}
?>