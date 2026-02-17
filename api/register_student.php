<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get raw POST data
$rawData = file_get_contents("php://input");
$data = json_decode($rawData);

// Log for debugging
error_log("Registration attempt: " . print_r($data, true));

// Check required fields
if (!$data || empty($data->email) || empty($data->password) || empty($data->name) || 
    empty($data->roll_number) || empty($data->course) || empty($data->branch) || 
    empty($data->graduation_year)) {
    
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "All required fields must be filled: Name, Email, Password, Roll Number, Course, Branch, and Graduation Year."
    ]);
    exit();
}

try {
    // 1. Domain Validation
    if (!str_ends_with($data->email, '@rjit.ac.in')) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Only @rjit.ac.in emails are allowed for students."
        ]);
        exit();
    }

    // 2. Password Requirements
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $data->password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Password must be 8+ chars with uppercase, number, and special char."
        ]);
        exit();
    }

    // 3. Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute(['email' => $data->email]);
    if ($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email already registered."
        ]);
        exit();
    }

    // Hash password
    $hash = password_hash($data->password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    error_log("Attempting to insert user: " . $data->email);

    // Insert into users - Using 'active' as per the enum values
    // Remove transaction for now to simplify
    $query = "INSERT INTO users (email, password_hash, role, status) VALUES (:e, :p, 'student', 'active') RETURNING user_id";
    $stmt = $db->prepare($query);
    $stmt->execute(['e' => $data->email, 'p' => $hash]);
    $user_id = $stmt->fetchColumn();
    
    if (!$user_id) {
        throw new Exception("Failed to get user_id after insertion");
    }
    
    error_log("User inserted with ID: " . $user_id);

    // Insert into profiles
    $prof_query = "INSERT INTO profiles (user_id, full_name) VALUES (:uid, :name)";
    $prof_stmt = $db->prepare($prof_query);
    $prof_stmt->execute(['uid' => $user_id, 'name' => $data->name]);
    
    error_log("Profile inserted for user ID: " . $user_id);

    // Try to insert into students table if it exists
    try {
        $student_query = "INSERT INTO students (user_id, roll_number, course, branch, graduation_year) 
                         VALUES (:uid, :roll, :course, :branch, :year)";
        $student_stmt = $db->prepare($student_query);
        $student_stmt->execute([
            'uid' => $user_id,
            'roll' => $data->roll_number,
            'course' => $data->course,
            'branch' => $data->branch,
            'year' => $data->graduation_year
        ]);
        error_log("Student record inserted for user ID: " . $user_id);
    } catch (Exception $e) {
        // Log the error but continue - students table might not exist
        error_log("Note: Students table insert failed (might not exist or have different structure): " . $e->getMessage());
        // Don't throw - this is optional
    }

    // Immediate verification - use a fresh query
    $verify_query = "SELECT user_id, email, role, status FROM users WHERE user_id = :user_id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute(['user_id' => $user_id]);
    $verified_user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verified_user) {
        throw new Exception("User verification failed - user not found after insertion. User ID: " . $user_id);
    }
    
    error_log("User verified: " . print_r($verified_user, true));

    // Success message for auto-approved registration
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Student registered successfully! Your account is now active.",
        "user" => [
            "user_id" => $user_id,
            "email" => $data->email,
            "name" => $data->name,
            "role" => "student",
            "status" => "active"
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Registration failed: " . $e->getMessage(),
        "debug" => "Check database tables and connections"
    ]);
    error_log("Registration error: " . $e->getMessage());
    
    // Also log the last PDO error if available
    if (isset($db) && $db->errorCode() != '00000') {
        $errorInfo = $db->errorInfo();
        error_log("PDO Error: " . print_r($errorInfo, true));
    }
}