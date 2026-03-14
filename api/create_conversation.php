<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_moderation_schema.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $currentUserId = (int)$auth->validateRequest();
    ensure_user_moderation_schema($db);

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }

    $otherUserId = isset($data['other_user_id']) ? (int)$data['other_user_id'] : 0;
    if ($otherUserId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'other_user_id is required']);
        exit;
    }

    if ($otherUserId === $currentUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot start a conversation with yourself']);
        exit;
    }

    $exists = $db->prepare("SELECT user_id FROM users WHERE user_id = :uid LIMIT 1");
    $exists->execute([':uid' => $otherUserId]);
    if (!$exists->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    moderation_assert_messaging_allowed($db, $currentUserId, 'You are restricted from sending messages right now.');

    $currentRole = moderation_get_user_role($db, $currentUserId);
    $otherRole = moderation_get_user_role($db, $otherUserId);
    if ($currentRole !== 'admin' && $otherRole === 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot start direct conversations with admin accounts.']);
        exit;
    }

    if ($currentRole !== 'admin' && in_array($currentRole, ['alumni', 'faculty'], true) && moderation_is_profile_private($db, $currentUserId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Private accounts must switch to public before sending direct messages.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'conversation_id' => (string)$otherUserId,
        'other_user_id' => $otherUserId
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create conversation',
        'error' => $e->getMessage()
    ]);
}
