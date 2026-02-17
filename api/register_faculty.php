<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

// 1. Basic validation (Faculty domain check recommended in Blueprint 3.B3)
if (!empty($data->email) && str_ends_with($data->email, '@rjit.ac.in')) {
    $hash = password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $query = "INSERT INTO users (email, password_hash, role, status) VALUES (:e, :p, 'faculty', 'pending')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['e' => $data->email, 'p' => $hash])) {
        echo json_encode(["message" => "Faculty account created. Pending admin approval."]);
    }
} else {
    echo json_encode(["message" => "Valid faculty email required."]);
}
?>