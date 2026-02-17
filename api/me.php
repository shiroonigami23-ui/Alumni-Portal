<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Origin, Accept");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

// Debug: Log all headers
error_log("=== api/me.php called ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
$allHeaders = getallheaders();
error_log("Headers received: " . print_r($allHeaders, true));

// 1. Instantiate DB & Auth
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    // 2. Validate Token (Gatekeeper)
    $user_id = $auth->validateRequest();
    
    error_log("Auth successful for user_id: " . $user_id);
    
    // 3. Fetch User Data with profile info
    $query = "SELECT 
            u.user_id, 
            u.email, 
            u.role, 
            u.status, 
            u.created_at,
            p.full_name,
            p.profile_picture_url AS profile_picture,  
            p.bio,
            p.linkedin_url AS linkedin,                
            p.github_url AS github,                   
            p.twitter_url AS twitter                   
          FROM users u 
          LEFT JOIN profiles p ON u.user_id = p.user_id 
          WHERE u.user_id = :user_id 
          LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user_data) {
        // 4. Get student data if exists
        try {
            $student_query = "SELECT roll_number, course, branch, graduation_year 
                              FROM students 
                              WHERE user_id = :user_id";
            $student_stmt = $db->prepare($student_query);
            $student_stmt->bindParam(":user_id", $user_id);
            $student_stmt->execute();
            
            if($student_stmt->rowCount() > 0) {
                $student_data = $student_stmt->fetch(PDO::FETCH_ASSOC);
                $user_data = array_merge($user_data, $student_data);
            }
        } catch (Exception $e) {
            // Students table might not exist, ignore
            error_log("Student data error: " . $e->getMessage());
        }
        
        // 5. Return Data
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "data" => $user_data
        ));
        
        error_log("User data returned for user_id: " . $user_id);
    } else {
        http_response_code(404);
        echo json_encode(array(
            "success" => false,
            "message" => "User not found."
        ));
        error_log("User not found for user_id: " . $user_id);
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => $e->getMessage()
    ));
    error_log("Auth error: " . $e->getMessage());
}

error_log("=== api/me.php finished ===");
?>