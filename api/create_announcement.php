<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->title) && !empty($data->content)) {
    // 1. Role Check
    $u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $u_stmt->execute(['uid' => $user_id]);
    $user = $u_stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Faculty Daily Limit Check (20/day) - Section 4.C
    if ($user['role'] === 'faculty') {
        $limit_stmt = $db->prepare("SELECT COUNT(*) FROM announcements WHERE creator_user_id = :uid AND created_at >= CURRENT_DATE");
        $limit_stmt->execute(['uid' => $user_id]);
        if ($limit_stmt->fetchColumn() >= 20) {
            http_response_code(429);
            echo json_encode(["message" => "Faculty daily announcement limit (20) reached."]);
            exit();
        }
        $status = 'published'; // Faculty go live immediately - Section 9.B
    } else {
        $status = 'pending_approval'; // Others require review
    }

    // 3. 3.5NF File Storage
    $filename = "ann_" . $user_id . "_" . time() . ".txt";
    $storage_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "announcements" . DIRECTORY_SEPARATOR;
    if (!file_exists($storage_path)) mkdir($storage_path, 0777, true);
    
    file_put_contents($storage_path . $filename, $data->content);
    $relative_path = "storage/announcements/" . $filename;

    // 4. Expiry (Section 9.B)
    $days = $data->expiry_days ?? 5;
    $expiry = date('Y-m-d H:i:s', strtotime("+$days days"));

    $query = "INSERT INTO announcements (creator_user_id, title, content_file_path, expires_at, status) 
              VALUES (:uid, :t, :p, :exp, :stat)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([
        'uid' => $user_id, 
        't' => $data->title, 
        'p' => $relative_path, 
        'exp' => $expiry,
        'stat' => $status
    ])) {
        echo json_encode([
            "message" => "Announcement posted successfully.",
            "status" => $status,
            "expires_at" => $expiry
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Title and Content are required."]);
}
?>