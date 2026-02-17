<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/Database.php';
include_once '../models/User.php';

// Instantiate DB & User
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->email) &&
    !empty($data->password) &&
    !empty($data->role)
) {
    // Set user property values
    $user->email = $data->email;
    $user->password = $data->password;
    $user->role = $data->role; // Must be 'student', 'alumni', 'faculty', or 'admin'

    // Check if email already exists
    if($user->emailExists()) {
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "Email already exists."));
    }
    // Create the user
    else if($user->create()) {
        http_response_code(201); // Created
        echo json_encode(array(
            "message" => "User was created.",
            "user_id" => $user->user_id
        ));
    }
    else {
        http_response_code(503); // Service Unavailable
        echo json_encode(array("message" => "Unable to create user."));
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("message" => "Incomplete data. Provide email, password, and role."));
}
?>