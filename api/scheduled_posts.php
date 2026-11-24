<?php
/**
 * Manage your scheduled posts.
 * GET                                  → upcoming + recent (published / failed / cancelled)
 * POST {action: cancel, scheduled_id}
 * POST {action: reschedule, scheduled_id, publish_at}
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_post_scheduler.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
ensure_scheduled_posts_schema($db);
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $s = $db->prepare("SELECT scheduled_id, content_path, publish_at, visibility_scope, status, published_post_id, last_error, poll_json, created_at
                       FROM scheduled_posts WHERE user_id = :u ORDER BY (status = 'pending') DESC, publish_at DESC LIMIT 50");
    $s->execute(['u' => $userId]);
    $rows = [];
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $payload = load_content_payload($db, (string)$r['content_path']);
        $poll = $r['poll_json'] ? json_decode($r['poll_json'], true) : null;
        $rows[] = [
            'scheduled_id' => (int)$r['scheduled_id'],
            'preview' => mb_substr((string)($payload['content'] ?? ''), 0, 200),
            'publish_at' => $r['publish_at'],
            'visibility_scope' => $r['visibility_scope'],
            'status' => $r['status'] ?: 'pending',
            'post_id' => $r['published_post_id'] ? (int)$r['published_post_id'] : null,
            'error' => $r['last_error'],
            'poll_question' => $poll['question'] ?? null,
        ];
    }
    $out(['success' => true, 'data' => $rows]);
}

$data = json_decode((string)file_get_contents('php://input'), true) ?: [];
$id = (int)($data['scheduled_id'] ?? 0);
$own = $db->prepare("SELECT status FROM scheduled_posts WHERE scheduled_id = :id AND user_id = :u");
$own->execute(['id' => $id, 'u' => $userId]);
$status = $own->fetchColumn();
if ($status === false) {
    $out(['success' => false, 'message' => 'Scheduled post not found.'], 404);
}
if (($status ?: 'pending') !== 'pending') {
    $out(['success' => false, 'message' => "This post is already $status."], 409);
}
switch ($data['action'] ?? '') {
    case 'cancel':
        $db->prepare("UPDATE scheduled_posts SET status = 'cancelled' WHERE scheduled_id = :id")->execute(['id' => $id]);
        $out(['success' => true, 'message' => 'Scheduled post cancelled.']);
    case 'reschedule':
        $ts = strtotime((string)($data['publish_at'] ?? ''));
        if (!$ts || $ts < time() + 60 || $ts > time() + 90 * 86400) {
            $out(['success' => false, 'message' => 'Pick a time between 1 minute and 90 days from now.'], 400);
        }
        $db->prepare("UPDATE scheduled_posts SET publish_at = :at WHERE scheduled_id = :id")->execute(['at' => gmdate('Y-m-d H:i:s', $ts), 'id' => $id]);
        $out(['success' => true, 'message' => 'Rescheduled.', 'publish_at' => gmdate('c', $ts)]);
    default:
        $out(['success' => false, 'message' => 'Unknown action.'], 400);
}
