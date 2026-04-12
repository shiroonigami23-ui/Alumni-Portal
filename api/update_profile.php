<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Database unavailable."
    ]);
    exit;
}
$auth = new Auth($db);

try {
    $user_id = (int)$auth->validateRequest();
    $roleStmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid LIMIT 1");
    $roleStmt->execute([':uid' => $user_id]);
    $userRole = strtolower((string)$roleStmt->fetchColumn());
    if ($userRole === 'student') {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Students cannot edit profile details."
        ]);
        exit;
    }
    $data = json_decode(file_get_contents("php://input"), true) ?: [];

    $check = $db->prepare("SELECT profile_id FROM profiles WHERE user_id = :uid");
    $check->execute([':uid' => $user_id]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    $fields = [
        'full_name', 'bio', 'skills', 'tech_stack',
        'profile_picture_url', 'cover_photo_url',
        'current_company', 'job_role',
        'department', 'branch', 'designation',
        'course', 'graduation_year',
        'contact_number',
        'joining_year', 'help_alumni_mates',
        'location_city', 'location_country',
        'personal_website',
        'linkedin_url', 'github_url', 'twitter_url'
    ];

    $payload = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $data)) {
            $value = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
            if ($f === 'joining_year') {
                if ($value === '' || $value === null) {
                    $value = null;
                } else {
                    $value = (int)$value;
                }
            }
            if ($f === 'graduation_year') {
                if ($value === '' || $value === null) {
                    $value = null;
                } else {
                    $value = (int)$value;
                }
            }
            $payload[$f] = $value;
        }
    }

    if (empty($payload)) {
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "No changes provided."
        ]);
        exit;
    }

    if ($exists) {
        $sets = [];
        $params = [':uid' => $user_id];
        foreach ($payload as $k => $v) {
            $sets[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        $sets[] = "updated_at = NOW()";
        $sql = "UPDATE profiles SET " . implode(', ', $sets) . " WHERE user_id = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    } else {
        $defaultsName = isset($payload['full_name']) && $payload['full_name'] !== ''
            ? $payload['full_name']
            : 'Alumni User';
        $columns = ['user_id', 'full_name'];
        $placeholders = [':uid', ':full_name'];
        $params = [':uid' => $user_id, ':full_name' => $defaultsName];
        foreach ($payload as $k => $v) {
            if ($k === 'full_name') continue;
            $columns[] = $k;
            $placeholders[] = ':' . $k;
            $params[':' . $k] = $v;
        }
        $sql = "INSERT INTO profiles (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Profile updated successfully."
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Failed to update profile.",
        "detail" => $e->getMessage()
    ]);
}
