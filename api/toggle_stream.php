<?php
/**
 * Start / stop your live session.
 * POST {action: start, title, description?, provider: jitsi|youtube, url? (YouTube link)}
 * POST {action: stop}
 */
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../models/Stream.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};
$data = json_decode((string)file_get_contents('php://input'), true) ?: [];
$stream = new Stream($db);

if (($data['action'] ?? 'stop') === 'start') {
    $r = $db->prepare("SELECT role FROM users WHERE user_id = :u");
    $r->execute(['u' => $userId]);
    if (!in_array($r->fetchColumn(), ['alumni', 'faculty', 'admin'], true)) {
        $out(['success' => false, 'message' => 'Live sessions can be hosted by alumni, faculty and admins.'], 403);
    }
    $session = $stream->start($userId, (string)($data['title'] ?? ''), (string)($data['description'] ?? ''), (string)($data['provider'] ?? 'jitsi'), (string)($data['url'] ?? ''));
    if (isset($session['error'])) {
        $out(['success' => false, 'message' => $session['error']], 400);
    }
    $auth->logAction($userId, 'STREAM_START', (string)$session['title']);
    $out(['success' => true, 'status' => 'success', 'message' => "You're live! 🎉", 'data' => $session]);
}
$ended = $stream->end($userId);
$auth->logAction($userId, 'STREAM_STOP', 'Live session ended');
$out(['success' => true, 'status' => 'success', 'message' => $ended ? 'Live session ended.' : 'You were not live.']);
