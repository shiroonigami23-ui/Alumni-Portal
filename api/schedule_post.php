<?php
/**
 * Schedule a post. Same rules as posting now (role, posting restrictions, moderation).
 * POST {content, publish_at (ISO-8601, any timezone), visibility_scope?, allow_comments?, gif_url?,
 *       poll?: {question, options[], duration_hours?}}
 */
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_post_scheduler.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};

ensure_scheduled_posts_schema($db);
$perms = scheduler_user_perms($db, $userId);
if (!user_can_create_top_level_posts($perms)) {
    $out(['success' => false, 'message' => 'This account cannot create top-level posts.'], 403);
}
moderation_assert_posting_allowed($db, $userId, 'Posting is currently restricted for this account.');

$data = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
$content = trim(mb_substr((string)($data['content'] ?? ''), 0, 5000));
$gif = trim((string)($data['gif_url'] ?? ''));
if ($content === '' && $gif === '') {
    $out(['success' => false, 'message' => 'Write something to schedule.'], 400);
}
$ts = strtotime((string)($data['publish_at'] ?? ''));
if (!$ts) {
    $out(['success' => false, 'message' => 'Pick a date and time to publish.'], 400);
}
if ($ts < time() + 60) {
    $out(['success' => false, 'message' => 'Pick a time at least a minute from now.'], 400);
}
if ($ts > time() + 90 * 86400) {
    $out(['success' => false, 'message' => 'Posts can be scheduled up to 90 days ahead.'], 400);
}
$allowedScopes = ['all', 'alumni', 'faculty', 'students', 'faculty_alumni', 'students_alumni', 'faculty_students'];
$scope = (string)($data['visibility_scope'] ?? 'all');
$scope = in_array($scope, $allowedScopes, true) ? $scope : 'all';
$attachments = [];
if ($gif !== '' && preg_match('#^https://#i', $gif)) {
    $attachments[] = ['type' => 'gif', 'url' => $gif, 'name' => 'GIF'];
}
$pollJson = null;
if (!empty($data['poll']) && is_array($data['poll'])) {
    $opts = array_values(array_filter(array_map(fn ($o) => trim((string)$o), (array)($data['poll']['options'] ?? []))));
    if (mb_strlen(trim((string)($data['poll']['question'] ?? ''))) < 3 || count($opts) < 2 || count($opts) > 6) {
        $out(['success' => false, 'message' => 'A poll needs a question and 2 to 6 options.'], 400);
    }
    $pollJson = json_encode(['question' => $data['poll']['question'], 'options' => $opts, 'duration_hours' => (int)($data['poll']['duration_hours'] ?? 0) ?: null]);
}

$pointer = store_content_payload($db, $userId, ['content' => $content, 'attachments' => $attachments], 'post');
$stmt = $db->prepare("INSERT INTO scheduled_posts (user_id, content_path, type, publish_at, visibility_scope, comments_enabled, poll_json, status)
                      VALUES (:u, :c, :t, :at, :v, :ce, :poll, 'pending') RETURNING scheduled_id");
$stmt->execute([
    'u' => $userId, 'c' => $pointer, 't' => $pollJson ? 'poll' : 'text', 'at' => gmdate('Y-m-d H:i:s', $ts), 'v' => $scope,
    'ce' => (string)($data['allow_comments'] ?? '1') === '0' ? 'false' : 'true', 'poll' => $pollJson,
]);
$id = (int)$stmt->fetchColumn();
$auth->logAction($userId, 'SCHEDULE_POST', 'Scheduled #' . $id . ' for ' . gmdate('c', $ts));
$out(['success' => true, 'message' => 'Post scheduled.', 'scheduled_id' => $id, 'publish_at' => gmdate('c', $ts)], 201);
