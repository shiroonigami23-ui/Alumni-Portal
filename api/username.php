<?php
/**
 * Your @handle.
 * GET  ?check=name           → { available, reason, suggestions[] }   (live check while typing)
 * GET  ?resolve=name         → { user_id, username, redirected }       (for /@name links; respects blocks)
 * GET                        → your handle + when you can change it next
 * POST { username }          → change it
 */
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once __DIR__ . '/_usernames.php';
require_once __DIR__ . '/_privacy.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$me = (int)$auth->validateRequest();
ensure_username_schema($db);
$role = privacy_role($db, $me);

$out = function (array $body, int $code = 200) {
    http_response_code($code);
    echo json_encode($body + ['success' => $code < 400]);
    exit;
};

$cur = $db->prepare("SELECT username, username_changed_at,
                            CASE WHEN username_changed_at IS NULL THEN NULL
                                 ELSE username_changed_at + (CAST(:d AS INTEGER) * INTERVAL '1 day') END AS next_change_at
                     FROM users WHERE user_id = :u");
$cur->execute([':u' => $me, ':d' => USERNAME_COOLDOWN_DAYS]);
$mine = $cur->fetch(PDO::FETCH_ASSOC) ?: ['username' => null, 'username_changed_at' => null, 'next_change_at' => null];
$canChangeNow = $mine['next_change_at'] === null || strtotime((string)$mine['next_change_at'] . ' UTC') <= time();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if (isset($_GET['resolve'])) {
        $hit = username_resolve($db, (string)$_GET['resolve']);
        if (!$hit || ($role !== 'admin' && privacy_is_blocked($db, $me, $hit['user_id']))) {
            $out(['message' => 'No one has that handle.'], 404);
        }
        $out(['data' => $hit]);
    }
    if (isset($_GET['check'])) {
        $u = username_normalise((string)$_GET['check']);
        if ($u === strtolower((string)$mine['username'])) {
            $out(['data' => ['username' => $u, 'available' => true, 'is_current' => true, 'reason' => null, 'suggestions' => []]]);
        }
        $problem = username_problem($u, $role);
        $available = $problem === null && username_available($db, $u, $me);
        $out(['data' => [
            'username' => $u,
            'available' => $available,
            'reason' => $problem ?? ($available ? null : 'That handle is taken.'),
            'suggestions' => $available ? [] : username_suggestions($db, $u !== '' ? $u : 'member', $me),
        ]]);
    }
    $out(['data' => [
        'username' => $mine['username'],
        'can_change_now' => $canChangeNow,
        'next_change_at' => $canChangeNow ? null : $mine['next_change_at'],
        'rules' => ['min' => USERNAME_MIN, 'max' => USERNAME_MAX, 'cooldown_days' => USERNAME_COOLDOWN_DAYS],
    ]]);
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$u = username_normalise((string)($data['username'] ?? ''));
if ($u === strtolower((string)$mine['username'])) {
    $out(['message' => 'That\'s already your handle.', 'data' => ['username' => $mine['username']]]);
}
if (!$canChangeNow && $role !== 'admin') {
    $out(['message' => 'You can change your handle again on ' . date('j M Y', strtotime((string)$mine['next_change_at'] . ' UTC')) . '.'], 429);
}
if ($problem = username_problem($u, $role)) {
    $out(['message' => $problem, 'suggestions' => username_suggestions($db, $u ?: 'member', $me)], 422);
}
if (!username_available($db, $u, $me)) {
    $out(['message' => 'That handle is taken.', 'suggestions' => username_suggestions($db, $u, $me)], 409);
}

try {
    $db->beginTransaction();
    if (!empty($mine['username']) && !preg_match('/^user\d+$/', (string)$mine['username'])) {
        $db->prepare("INSERT INTO username_history (user_id, username) VALUES (:u, :n)")->execute([':u' => $me, ':n' => $mine['username']]);
    }
    $db->prepare("UPDATE users SET username = :n, username_changed_at = NOW() WHERE user_id = :u")->execute([':n' => $u, ':u' => $me]);
    $db->commit();
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($e->getCode() === '23505') {  // someone grabbed it a millisecond earlier — the unique index caught it
        $out(['message' => 'That handle was just taken.', 'suggestions' => username_suggestions($db, $u, $me)], 409);
    }
    error_log('username change: ' . $e->getMessage());
    $out(['message' => 'Could not change your handle right now.'], 500);
}
try {
    $auth->logAction($me, 'USERNAME_CHANGE', '@' . ($mine['username'] ?? '?') . ' → @' . $u);
} catch (Throwable $e) {
}
$out(['message' => "You're now @$u.", 'data' => ['username' => $u]]);
