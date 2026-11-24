<?php
/** POST {post_id, question, options[], duration_hours?} — attach a poll to your own post. */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_polls.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$data = json_decode((string)file_get_contents('php://input'), true) ?: [];
$postId = (int)($data['post_id'] ?? 0);

$own = $db->prepare("SELECT user_id FROM posts WHERE post_id = :p");
$own->execute(['p' => $postId]);
$owner = $own->fetchColumn();
if ($owner === false) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
}
if ((int)$owner !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only add a poll to your own post.']);
    exit;
}
try {
    [$pollId, $err] = poll_create($db, $postId, (string)($data['question'] ?? ''), (array)($data['options'] ?? []),
        isset($data['duration_hours']) ? (int)$data['duration_hours'] : (isset($data['expires_at']) && $data['expires_at'] ? max(1, (int)ceil((strtotime($data['expires_at']) - time()) / 3600)) : null));
    if ($err) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $err]);
        exit;
    }
    $auth->logAction($userId, 'CREATE_POLL', "Poll #$pollId on post #$postId");
    echo json_encode(['success' => true, 'message' => 'Poll created successfully.', 'poll_id' => $pollId]);
} catch (Throwable $e) {
    error_log('create_poll: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not create the poll.']);
}
