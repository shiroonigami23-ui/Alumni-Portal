<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/Database.php';
include_once '../models/User.php';
include_once '../models/Session.php';
include_once '../middleware/Auth.php';
include_once '../helpers/StudentLifecycleHelper.php';
include_once __DIR__ . '/_community_schema.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode(array(
        "success" => false,
        "message" => "Database unavailable. Please start your database service and try again."
    ));
    exit();
}
ensure_community_schema($db);

$user = new User($db);
$session = new Session($db);
$authGuard = new Auth($db);

// Device ban pre-check
if ($authGuard->isCurrentDeviceBanned()) {
    http_response_code(403);
    echo json_encode(array(
        "success" => false,
        "message" => "This device is banned from accessing the platform."
    ));
    exit();
}

$raw = file_get_contents("php://input");
$data = json_decode($raw);

if (!is_object($data)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid JSON body."));
    exit();
}

if (!empty($data->email) && !empty($data->password)) {
    // Set both email AND password on the user object
    $user->email = $data->email;
    $user->password = $data->password;

    // Attempt Login
    if ($user->login()) {
        if ($user->role === 'student' && StudentLifecycleHelper::isEligibleForAlumniRoleByEmail((string)$user->email)) {
            try {
                $gradYear = StudentLifecycleHelper::expectedGraduationYearForEmail((string)$user->email);
                $db->beginTransaction();
                $db->prepare("UPDATE users SET role = 'alumni', updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND role = 'student'")
                   ->execute(['uid' => $user->user_id]);
                if ($gradYear) {
                    $db->prepare("UPDATE profiles SET graduation_year = :gy, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid")
                       ->execute(['gy' => $gradYear, 'uid' => $user->user_id]);
                }
                $db->commit();
                $user->role = 'alumni';
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
            }
        }

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
                "is_moderator" => (bool)$user->is_moderator,
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
