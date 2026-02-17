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

if (!empty($data->poll_id)) {
    try {
        // 1. Fetch Poll & Post details to check ownership/role
        $query = "SELECT p.poll_id, po.user_id as creator_id, u.role 
                  FROM polls p 
                  JOIN posts po ON p.post_id = po.post_id 
                  JOIN users u ON :uid = u.user_id 
                  WHERE p.poll_id = :pid";
        
        $stmt = $db->prepare($query);
        $stmt->execute(['pid' => $data->poll_id, 'uid' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            echo json_encode(["message" => "Poll not found."]);
            exit();
        }

        // 2. Authorization Check (Section 4 & 5): Creator, Faculty, or Admin
        $is_creator = ($result['creator_id'] == $user_id);
        $is_authority = ($result['role'] === 'admin' || $result['role'] === 'faculty');

        if ($is_creator || $is_authority) {
            // 3. End Poll by setting expiry to NOW()
            $update = "UPDATE polls SET expires_at = NOW() WHERE poll_id = :pid";
            $u_stmt = $db->prepare($update);
            $u_stmt->execute(['pid' => $data->poll_id]);

            $auth->logAction($user_id, "END_POLL", "Poll ID " . $data->poll_id . " ended manually.");
            echo json_encode(["message" => "Poll ended successfully. Results are now final."]);
        } else {
            http_response_code(403);
            echo json_encode(["message" => "Unauthorized. Only the creator or faculty/admin can end this poll."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Poll ID required."]);
}
?>