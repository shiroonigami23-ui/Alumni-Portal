<?php
/**
 * Block / unblock people.
 * POST { user_id, action: "block" | "unblock" }   (action defaults to toggle)
 * GET  ?action=list                               people you've blocked
 *
 * Blocking is mutual in effect: neither person sees the other's profile, posts, comments, search results or
 * messages, both follow links are removed, and pending follow requests are dropped. The other person isn't told.
 * Staff (faculty/admin) can't be blocked — use Report instead — so moderation keeps working.
 */
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once __DIR__ . '/_privacy.php';
require_once __DIR__ . '/_profile_media.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$me = (int)$auth->validateRequest();

$out = function (array $body, int $code = 200) {
    http_response_code($code);
    echo json_encode($body + ['success' => $code < 400]);
    exit;
};

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$data = $method === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: $_POST) : $_GET;
$action = strtolower((string)($data['action'] ?? ($method === 'GET' ? 'list' : 'toggle')));

try {
    if ($action === 'list') {
        $s = $db->prepare("
            SELECT b.blocked_user_id AS user_id, b.created_at,
                   COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name, u.role, p.profile_picture_url
            FROM blocks b JOIN users u ON u.user_id = b.blocked_user_id LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE b.blocker_user_id = :me ORDER BY b.created_at DESC
        ");
        $s->execute([':me' => $me]);
        $rows = array_map(function ($r) use ($db) {
            $r['user_id'] = (int)$r['user_id'];
            $r['profile_picture_url'] = resolve_profile_media_url($db, $r['user_id'], $r['profile_picture_url'] ?? '', 'profile_picture_url', 'profile_avatar');
            return $r;
        }, $s->fetchAll(PDO::FETCH_ASSOC));
        $out(['data' => $rows]);
    }

    $target = (int)($data['user_id'] ?? 0);
    if ($target <= 0 || $target === $me) {
        $out(['message' => 'Choose someone to block.'], 400);
    }
    $role = privacy_role($db, $target);
    if ($role === '') {
        $out(['message' => 'User not found.'], 404);
    }

    $exists = $db->prepare("SELECT 1 FROM blocks WHERE blocker_user_id = :me AND blocked_user_id = :t");
    $exists->execute([':me' => $me, ':t' => $target]);
    $isBlocked = (bool)$exists->fetchColumn();
    if ($action === 'toggle') {
        $action = $isBlocked ? 'unblock' : 'block';
    }

    if ($action === 'unblock') {
        $db->prepare("DELETE FROM blocks WHERE blocker_user_id = :me AND blocked_user_id = :t")->execute([':me' => $me, ':t' => $target]);
        $out(['blocked' => false, 'message' => 'Unblocked. They can find and message you again.']);
    }
    if ($action !== 'block') {
        $out(['message' => 'Unknown action.'], 400);
    }
    if (in_array($role, ['admin', 'faculty'], true)) {
        $out(['message' => 'Faculty and admins can\'t be blocked. If something is wrong, please use Report.'], 403);
    }

    $db->beginTransaction();
    $db->prepare("INSERT INTO blocks (blocker_user_id, blocked_user_id) VALUES (:me, :t) ON CONFLICT (blocker_user_id, blocked_user_id) DO NOTHING")
       ->execute([':me' => $me, ':t' => $target]);
    $db->prepare("DELETE FROM connections WHERE (requester_user_id = :me AND addressee_user_id = :t) OR (requester_user_id = :t AND addressee_user_id = :me)")
       ->execute([':me' => $me, ':t' => $target]);
    $db->commit();
    try {
        $auth->logAction($me, 'USER_BLOCK', "Blocked user #$target");
    } catch (Throwable $e) {
    }
    $out(['blocked' => true, 'message' => 'Blocked. You won\'t see each other anywhere on the portal.']);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('block_user: ' . $e->getMessage());
    $out(['message' => 'Could not update block status.'], 500);
}
