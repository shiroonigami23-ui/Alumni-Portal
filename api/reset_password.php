<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->token) && !empty($data->new_password)) {
    // 1. Validate Token and check age (1 hour limit)
    $query = "SELECT email FROM password_resets 
              WHERE token = :token 
              AND created_at > NOW() - INTERVAL '1 hour'";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['token' => $data->token]);
    $reset_req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reset_req) {
        $new_hash = password_hash($data->new_password, PASSWORD_BCRYPT);
        
        // 2. Update user password
        $update = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
        if ($update->execute(['hash' => $new_hash, 'email' => $reset_req['email']])) {
            
            // 3. Clean up: Delete used token
            $db->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $reset_req['email']]);
            
            echo json_encode(["message" => "Password updated successfully."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Invalid or expired token."]);
    }
}
?>