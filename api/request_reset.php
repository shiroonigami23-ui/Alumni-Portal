<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email)) {
    // Check if user exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute(['email' => $data->email]);

    if ($stmt->rowCount() > 0) {
        $token = bin2hex(random_bytes(16)); // Secure 32-char token
        
        // Clear old tokens for this email
        $db->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $data->email]);
        
        // Save new token
        $ins = $db->prepare("INSERT INTO password_resets (email, token) VALUES (:email, :token)");
        if ($ins->execute(['email' => $data->email, 'token' => $token])) {
            echo json_encode([
                "message" => "Reset token generated.",
                "simulation_link" => "http://localhost/alumni_portal/reset.php?token=" . $token,
                "token" => $token // In production, this only goes to the user's email
            ]);
        }
    } else {
        // Security best practice: Don't reveal if email exists
        echo json_encode(["message" => "If the email exists, a reset link has been sent."]);
    }
}
?>