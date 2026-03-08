<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];

    $current = trim((string)($data['current_password'] ?? ''));
    $next = trim((string)($data['new_password'] ?? ''));

    if ($current === '' || $next === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Current password and new password are required.'
        ]);
        exit;
    }

    if (strlen($next) < 8) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'New password must be at least 8 characters.'
        ]);
        exit;
    }

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute(['uid' => $user_id]);
    $hash = (string)$stmt->fetchColumn();

    if ($hash === '' || !password_verify($current, $hash)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Current password is incorrect.'
        ]);
        exit;
    }

    if (password_verify($next, $hash)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'New password must be different from current password.'
        ]);
        exit;
    }

    $newHash = password_hash($next, PASSWORD_BCRYPT, ['cost' => 12]);
    $up = $db->prepare("UPDATE users SET password_hash = :ph WHERE user_id = :uid");
    $up->execute(['ph' => $newHash, 'uid' => $user_id]);

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'message' => 'Password updated successfully.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

