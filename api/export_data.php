<?php
/**
 * "Download my data" — everything you created on the portal, as one JSON file.
 * GET  → JSON (the profile page saves it as a file). Nothing is written to disk.
 * Other people's private data is never included (only their user ids / names on things you interacted with).
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");
header('Cache-Control: no-store');

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once __DIR__ . '/_content_store.php';
require_once __DIR__ . '/../helpers/Cache.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$uid = (int)$auth->validateRequest();

// One export per minute per person is plenty and keeps this heavy query from being abused.
$lockKey = Cache::key('export_data', (string)$uid);
if (Cache::get($lockKey)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please wait a minute before exporting again.']);
    exit;
}
Cache::set($lockKey, 1, 60);

$text = function (?string $pointer) use ($db): string {
    if (!$pointer) {
        return '';
    }
    try {
        return (string)(load_content_payload($db, $pointer)['content'] ?? '');
    } catch (Throwable $e) {
        return '';
    }
};
$rows = function (string $sql, array $params = []) use ($db): array {
    try {
        $s = $db->prepare($sql);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];   // a feature table that doesn't exist on this install is simply skipped
    }
};
$p = [':u' => $uid];

$account = $rows("SELECT user_id, email, role, status, created_at, last_login FROM users WHERE user_id = :u", $p)[0]
        ?? $rows("SELECT user_id, email, role, status, created_at FROM users WHERE user_id = :u", $p)[0] ?? [];
$profile = $rows("SELECT * FROM profiles WHERE user_id = :u", $p)[0] ?? [];
unset($profile['profile_id']);

$posts = array_map(function ($r) use ($text) {
    $r['content'] = $text($r['content_file_path']);
    unset($r['content_file_path']);
    return $r;
}, $rows("SELECT post_id, post_type, title, content_file_path, visibility_scope, status, created_at FROM posts WHERE user_id = :u ORDER BY created_at", $p));

$comments = array_map(function ($r) use ($text) {
    $r['content'] = $text($r['content_file_path']);
    unset($r['content_file_path']);
    return $r;
}, $rows("SELECT comment_id, post_id, parent_comment_id, content_file_path, created_at FROM comments WHERE user_id = :u ORDER BY created_at", $p));

$messages = array_map(function ($r) use ($text) {
    $r['content'] = $text($r['content_file_path']);
    unset($r['content_file_path']);
    return $r;
}, $rows("SELECT message_id, receiver_user_id AS to_user_id, content_file_path, created_at FROM messages
          WHERE sender_user_id = :u AND deleted_at IS NULL ORDER BY created_at", $p));

$scheduled = array_map(function ($r) use ($text) {
    $r['content'] = $text($r['content_path']);
    unset($r['content_path']);
    return $r;
}, $rows("SELECT scheduled_id, content_path, publish_at, status, published_post_id FROM scheduled_posts WHERE user_id = :u", $p));

$stories = array_map(function ($r) use ($text) {
    $r['content'] = $text($r['story_content_file_path']);
    unset($r['story_content_file_path']);
    return $r;
}, $rows("SELECT story_id, title, story_content_file_path, is_featured, view_count, created_at FROM success_stories WHERE alumni_user_id = :u", $p));

$nameOf = "COALESCE(NULLIF(TRIM(pp.full_name), ''), 'RJIT member')";
$export = [
    'success' => true,
    'exported_at' => gmdate('c'),
    'portal' => 'RJIT Alumni Portal',
    'account' => $account,
    'profile' => $profile,
    'posts' => $posts,
    'scheduled_posts' => $scheduled,
    'comments' => $comments,
    'messages_sent' => $messages,
    'likes' => $rows("SELECT post_id, reaction_type, created_at FROM likes WHERE user_id = :u ORDER BY created_at", $p),
    'reposts' => $rows("SELECT post_id, created_at FROM reposts WHERE user_id = :u ORDER BY created_at", $p),
    'poll_votes' => $rows("SELECT poll_id, option_id, created_at FROM poll_votes WHERE user_id = :u", $p),
    'following' => $rows("SELECT c.addressee_user_id AS user_id, $nameOf AS name, c.status, c.created_at FROM connections c
                          LEFT JOIN profiles pp ON pp.user_id = c.addressee_user_id WHERE c.requester_user_id = :u", $p),
    'followers' => $rows("SELECT c.requester_user_id AS user_id, $nameOf AS name, c.status, c.created_at FROM connections c
                          LEFT JOIN profiles pp ON pp.user_id = c.requester_user_id WHERE c.addressee_user_id = :u", $p),
    'jobs_posted' => $rows("SELECT job_id, job_title, company_name, location, job_type, status, created_at FROM jobs WHERE poster_id = :u", $p),
    'job_applications' => $rows("SELECT application_id, job_id, status, created_at FROM job_applications WHERE applicant_id = :u", $p),
    'event_rsvps' => $rows("SELECT event_id, rsvp_status, created_at FROM event_rsvps WHERE user_id = :u", $p),
    'referral_offers' => $rows("SELECT offer_id, company, roles, locations, experience, slots, status, created_at FROM referral_offers WHERE owner_id = :u", $p),
    'referral_requests' => $rows("SELECT request_id, offer_id, message, resume_url, status, created_at FROM referral_requests WHERE requester_id = :u", $p),
    'mentorship_requests' => $rows("SELECT request_id, mentor_id, mentee_id, message, status, created_at FROM mentorship_requests WHERE mentee_id = :u OR mentor_id = :u", $p),
    'success_stories' => $stories,
    'resources_shared' => $rows("SELECT resource_id, title, description, category, file_url, created_at FROM resources WHERE uploaded_by = :u", $p),
    'blocked_users' => $rows("SELECT blocked_user_id AS user_id, created_at FROM blocks WHERE blocker_user_id = :u", $p),
];
$export['counts'] = array_map(fn ($v) => is_array($v) ? count($v) : null, array_filter($export, 'is_array'));
unset($export['counts']['account'], $export['counts']['profile']);

try {
    $auth->logAction($uid, 'DATA_EXPORT', 'Downloaded personal data');
} catch (Throwable $e) {
}
echo json_encode($export, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
