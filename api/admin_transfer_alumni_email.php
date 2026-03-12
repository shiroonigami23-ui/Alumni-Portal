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
    $actorId = (int)$auth->validateRequest();
    $roleStmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
    $roleStmt->execute(['uid' => $actorId]);
    $actorRole = (string)$roleStmt->fetchColumn();
    if ($actorRole !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Only admin can transfer alumni email."]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $targetId = (int)($data['target_user_id'] ?? 0);
    $newEmail = strtolower(trim((string)($data['new_email'] ?? '')));
    if ($targetId <= 0 || $newEmail === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "target_user_id and new_email are required."]);
        exit;
    }
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid email format."]);
        exit;
    }

    $db->beginTransaction();
    $targetStmt = $db->prepare("SELECT user_id, role, email FROM users WHERE user_id = :uid FOR UPDATE");
    $targetStmt->execute(['uid' => $targetId]);
    $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        throw new Exception("Target user not found.");
    }
    if (($target['role'] ?? '') !== 'alumni') {
        throw new Exception("Email transfer is allowed only for alumni accounts.");
    }

    $dupStmt = $db->prepare("SELECT 1 FROM users WHERE lower(email) = lower(:e) AND user_id <> :uid LIMIT 1");
    $dupStmt->execute(['e' => $newEmail, 'uid' => $targetId]);
    if ($dupStmt->fetchColumn()) {
        throw new Exception("Email already in use by another account.");
    }

    $db->prepare("UPDATE users SET email = :email, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid")
       ->execute(['email' => $newEmail, 'uid' => $targetId]);

    $auth->logAction($actorId, "TRANSFER_ALUMNI_EMAIL", "Target {$targetId}: {$target['email']} -> {$newEmail}");
    $db->commit();

    echo json_encode(["success" => true, "message" => "Alumni email transferred successfully."]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
