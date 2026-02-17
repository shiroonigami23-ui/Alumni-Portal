<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/Database.php';
include_once '../models/User.php';
include_once '../models/Session.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$session = new Session($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    // Set both email AND password on the user object
    $user->email = $data->email;
    $user->password = $data->password;

    // Attempt Login
    if ($user->login()) {

        if ($user->status === 'banned' || $user->status === 'suspended') {
            http_response_code(403);
            echo json_encode(array("success" => false, "message" => "Account is " . $user->status));
            exit();
        }

        // Generate Token
        $token = bin2hex(random_bytes(32));
        $expiry_seconds = 604800; // 7 Days
        $expires_at = date('Y-m-d H:i:s', time() + $expiry_seconds);

        if ($session->create($user->user_id, $token, $expires_at)) {
            // Generate CSRF Token for session
            require_once __DIR__ . '/../middleware/Security.php';
            $csrf_token = Security::generateCSRFToken();

            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Login successful.",
                "token" => $token,
                "csrf_token" => $csrf_token,
                "user_id" => $user->user_id,
                "email" => $user->email,  // Make sure this is included
                "role" => $user->role,
                "status" => $user->status, // Also include status
                "expires_at" => $expires_at
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Session creation failed."));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Invalid email or password."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data."));
}
