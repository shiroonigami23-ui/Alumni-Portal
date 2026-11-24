<?php
/**
 * Follow system (with requests for private accounts).
 *
 * POST { user_id }                              toggle: follow / unfollow / send or cancel a request
 * POST { action: "approve" | "decline", user_id }   owner answers a request from user_id
 * POST { action: "remove_follower", user_id }   owner removes someone who follows them
 * GET  ?action=requests                         pending requests sent to me
 * GET  ?action=state&user_id=N                  my follow state toward N
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once __DIR__ . '/_privacy.php';
require_once __DIR__ . '/_profile_media.php';

function out(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body + ['success' => $code < 400, 'status' => $code < 400 ? 'success' : 'error']);
    exit;
}

$db = (new Database())->getConnection();
$auth = new Auth($db);
$me = (int)$auth->validateRequest();
privacy_ensure_schema($db);

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $data = $method === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: $_POST) : $_GET;
    $action = strtolower(trim((string)($data['action'] ?? ($_GET['action'] ?? 'toggle'))));
    $target = (int)($data['user_id'] ?? 0);

    if ($action === 'requests') {
        $s = $db->prepare("
            SELECT c.requester_user_id AS user_id, c.created_at,
                   COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name,
                   u.role, p.branch, p.current_company, p.graduation_year, p.profile_picture_url
            FROM connections c
            JOIN users u ON u.user_id = c.requester_user_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE c.addressee_user_id = :me AND c.status = 'pending'
            ORDER BY c.created_at DESC
            LIMIT 200
        ");
        $s->execute([':me' => $me]);
        $rows = array_map(function ($r) use ($db) {
            $r['user_id'] = (int)$r['user_id'];
            $r['profile_picture_url'] = resolve_profile_media_url($db, $r['user_id'], $r['profile_picture_url'] ?? '', 'profile_picture_url', 'profile_avatar');
            return $r;
        }, $s->fetchAll(PDO::FETCH_ASSOC));
        out(200, ['data' => $rows, 'count' => count($rows)]);
    }

    if ($target <= 0) {
        out(400, ['message' => 'user_id is required.']);
    }
    if ($target === $me) {
        out(400, ['message' => 'You cannot follow yourself.']);
    }
    $exists = $db->prepare("SELECT 1 FROM users WHERE user_id = :u");
    $exists->execute([':u' => $target]);
    if (!$exists->fetchColumn()) {
        out(404, ['message' => 'User not found.']);
    }

    if ($action === 'state') {
        out(200, ['data' => ['follow_state' => privacy_follow_state($db, $me, $target), 'is_private' => privacy_is_private($db, $target)]]);
    }

    if ($action === 'approve' || $action === 'decline') {
        if ($action === 'approve') {
            $s = $db->prepare("UPDATE connections SET status = 'accepted', accepted_at = NOW() WHERE requester_user_id = :r AND addressee_user_id = :me AND status = 'pending'");
        } else {
            $s = $db->prepare("DELETE FROM connections WHERE requester_user_id = :r AND addressee_user_id = :me AND status = 'pending'");
        }
        $s->execute([':r' => $target, ':me' => $me]);
        if ($s->rowCount() === 0) {
            out(404, ['message' => 'No pending request from this user.']);
        }
        if ($action === 'approve') {
            privacy_notify($db, $target, $me, 'accepted', privacy_display_name($db, $me) . ' accepted your follow request.');
        }
        out(200, ['message' => $action === 'approve' ? 'Request approved.' : 'Request declined.']);
    }

    if ($action === 'remove_follower') {
        $s = $db->prepare("DELETE FROM connections WHERE requester_user_id = :r AND addressee_user_id = :me");
        $s->execute([':r' => $target, ':me' => $me]);
        out(200, ['message' => 'Follower removed.', 'removed' => $s->rowCount() > 0]);
    }

    if ($action !== 'toggle' && $action !== 'follow' && $action !== 'unfollow') {
        out(400, ['message' => 'Unknown action.']);
    }

    $state = privacy_follow_state($db, $me, $target);

    if ($state === 'following' || $state === 'requested' || $action === 'unfollow') {
        if ($action === 'follow') {
            out(200, ['connected' => $state === 'following', 'follow_state' => $state, 'message' => 'Already ' . $state . '.']);
        }
        $db->prepare("DELETE FROM connections WHERE requester_user_id = :r AND addressee_user_id = :a")
           ->execute([':r' => $me, ':a' => $target]);
        out(200, [
            'connected' => false,
            'follow_state' => 'none',
            'message' => $state === 'requested' ? 'Follow request cancelled.' : 'Unfollowed.',
        ]);
    }

    if (privacy_is_blocked($db, $me, $target)) {
        out(403, ['message' => 'You cannot follow this person.']);
    }

    $private = privacy_is_private($db, $target);
    $status = $private ? 'pending' : 'accepted';
    $db->prepare("
        INSERT INTO connections (requester_user_id, addressee_user_id, status, accepted_at)
        VALUES (:r, :a, :st, CASE WHEN :st2 = 'accepted' THEN NOW() ELSE NULL END)
        ON CONFLICT (requester_user_id, addressee_user_id)
        DO UPDATE SET status = EXCLUDED.status, accepted_at = EXCLUDED.accepted_at, created_at = NOW()
    ")->execute([':r' => $me, ':a' => $target, ':st' => $status, ':st2' => $status]);

    $name = privacy_display_name($db, $me);
    if ($private) {
        privacy_notify($db, $target, $me, 'follow_request', $name . ' asked to follow you.');
        out(200, ['connected' => false, 'follow_state' => 'requested', 'message' => 'Follow request sent.']);
    }
    privacy_notify($db, $target, $me, 'follow', $name . ' started following you.');
    out(200, ['connected' => true, 'follow_state' => 'following', 'message' => 'Now following.']);
} catch (Throwable $e) {
    error_log('connect_user: ' . $e->getMessage());
    out(500, ['message' => 'Could not update follow status.']);
}
