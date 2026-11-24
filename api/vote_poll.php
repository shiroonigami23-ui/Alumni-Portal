<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
require_once __DIR__ . '/_privacy.php';
/** POST {poll_id, option_id} — one vote per person; returns fresh results. */
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_polls.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$data = json_decode((string)file_get_contents('php://input'), true) ?: [];
$pollId = (int)($data['poll_id'] ?? 0);
$optionId = (int)($data['option_id'] ?? 0);
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};
if ($pollId <= 0 || $optionId <= 0) {
    $out(['success' => false, 'message' => 'Choose an option to vote.'], 400);
}

$poll = $db->prepare("SELECT p.post_id, o.option_id, (p.is_active AND (p.expires_at IS NULL OR p.expires_at > NOW())) AS is_open
                      FROM polls p LEFT JOIN poll_options o ON o.poll_id = p.poll_id AND o.option_id = :o
                      WHERE p.poll_id = :p");
$poll->execute(['p' => $pollId, 'o' => $optionId]);
$row = $poll->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    $out(['success' => false, 'message' => 'Poll not found.'], 404);
}
privacy_require_post($db, $userId, (int)$row['post_id']);
if (!$row['option_id']) {
    $out(['success' => false, 'message' => "That option doesn't belong to this poll."], 400);
}
if (!filter_var($row['is_open'], FILTER_VALIDATE_BOOLEAN)) {
    $out(['success' => false, 'message' => 'This poll has closed.'], 409);
}

try {
    $db->beginTransaction();
    $db->prepare("INSERT INTO poll_votes (user_id, poll_id, option_id) VALUES (:u, :p, :o)")
       ->execute(['u' => $userId, 'p' => $pollId, 'o' => $optionId]);
    $db->prepare("UPDATE poll_options SET vote_count = vote_count + 1 WHERE option_id = :o")->execute(['o' => $optionId]);
    $db->commit();
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($e->getCode() === '23505') {
        $out(['success' => false, 'message' => 'You have already voted in this poll.'], 409);
    }
    error_log('vote_poll: ' . $e->getMessage());
    $out(['success' => false, 'message' => 'Could not record your vote.'], 500);
}
$fresh = polls_for_posts($db, [(int)$row['post_id']], $userId);
$out(['success' => true, 'message' => 'Vote recorded.', 'poll' => $fresh[(int)$row['post_id']] ?? null]);
