<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once __DIR__ . '/_password_reset_schema.php';

$database = new Database();
$db = $database->getConnection();
ensure_password_reset_schema($db);

$data = json_decode(file_get_contents("php://input"));
$token = trim((string)($data->token ?? ''));
$newPassword = (string)($data->new_password ?? '');

if ($token === '' || $newPassword === '') {
    http_response_code(400);
    echo json_encode(["message" => "Token and new password are required."]);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(["message" => "Password must be at least 8 characters long."]);
    exit;
}

$tokenHash = hash('sha256', $token);
$query = "SELECT email FROM password_resets 
          WHERE (token_hash = :token_hash OR token = :legacy_token)
            AND (expires_at IS NULL OR expires_at > NOW())
            AND used_at IS NULL
          ORDER BY created_at DESC
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->execute([
    'token_hash' => $tokenHash,
    'legacy_token' => $token
]);
$resetReq = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resetReq) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid or expired token."]);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$update = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
if (!$update->execute(['hash' => $newHash, 'email' => $resetReq['email']])) {
    http_response_code(500);
    echo json_encode(["message" => "Failed to update password."]);
    exit;
}

$db->prepare("UPDATE password_resets SET used_at = NOW(), expires_at = NOW() WHERE email = :email")
   ->execute(['email' => $resetReq['email']]);

echo json_encode(["message" => "Password updated successfully."]);
