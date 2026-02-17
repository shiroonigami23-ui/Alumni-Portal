<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

echo "Testing Auth middleware...\n\n";

try {
    $user_id = $auth->validateRequest();
    echo "Auth successful! User ID: " . $user_id . "\n\n";
    
    // Now test the user query
    $query = "SELECT 
                u.user_id, 
                u.email, 
                u.role, 
                u.status, 
                u.created_at,
                p.full_name
              FROM users u 
              LEFT JOIN profiles p ON u.user_id = p.user_id 
              WHERE u.user_id = :user_id 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User data fetched:\n";
    print_r($user_data);
    
} catch (Exception $e) {
    echo "Auth failed: " . $e->getMessage() . "\n";
    
    // Show headers for debugging
    echo "\nHeaders received:\n";
    $headers = null;
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        $headers = $_SERVER;
    }
    
    foreach ($headers as $key => $value) {
        if (strpos($key, 'HTTP_') === 0 || $key === 'Authorization' || $key === 'authorization') {
            echo "$key: $value\n";
        }
    }
}
?>