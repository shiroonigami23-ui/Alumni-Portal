<?php
/**
 * Privacy & visibility settings.
 * POST any subset of: is_private, show_email, show_contact, open_to_mentor, open_to_refer, open_to_work
 * GET  returns the current values.
 * Students are always public (portal policy). Going public auto-approves pending follow requests.
 */
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once __DIR__ . '/_privacy.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$uid = (int)$auth->validateRequest();
privacy_ensure_schema($db);

$role = privacy_role($db, $uid);
$fields = ['is_private', 'show_email', 'show_contact', 'open_to_mentor', 'open_to_refer', 'open_to_work'];

function current_settings(PDO $db, int $uid, string $role): array
{
    $s = $db->prepare("SELECT COALESCE(is_private, FALSE) AS is_private, COALESCE(show_email, TRUE) AS show_email,
                              COALESCE(show_contact, FALSE) AS show_contact, COALESCE(open_to_mentor, FALSE) AS open_to_mentor,
                              COALESCE(open_to_refer, FALSE) AS open_to_refer, COALESCE(open_to_work, FALSE) AS open_to_work
                       FROM profiles WHERE user_id = :u");
    $s->execute([':u' => $uid]);
    $row = $s->fetch(PDO::FETCH_ASSOC) ?: ['is_private' => false, 'show_email' => true, 'show_contact' => false,
                                            'open_to_mentor' => false, 'open_to_refer' => false, 'open_to_work' => false];
    $row = array_map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN), $row);
    if ($role === 'student') {
        $row['is_private'] = false;
    }
    $rq = $db->prepare("SELECT COUNT(*) FROM connections WHERE addressee_user_id = :u AND status = 'pending'");
    $rq->execute([':u' => $uid]);
    $row['pending_requests'] = (int)$rq->fetchColumn();
    $row['privacy_locked'] = $role === 'student';
    return $row;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => true, 'data' => current_settings($db, $uid, $role)]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

$updates = [];
foreach ($fields as $f) {
    if (!array_key_exists($f, $data)) {
        continue;
    }
    $v = privacy_bool($data[$f]);
    if ($v === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$f must be true or false."]);
        exit;
    }
    $updates[$f] = $v;
}
if (!$updates) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nothing to update.']);
    exit;
}

$enforcedPublic = false;
if ($role === 'student' && array_key_exists('is_private', $updates)) {
    $enforcedPublic = $updates['is_private'] === true;
    $updates['is_private'] = false;
}
if ($role !== 'alumni' && $role !== 'faculty' && $role !== 'admin') {
    unset($updates['open_to_mentor'], $updates['open_to_refer']);
}

try {
    $db->beginTransaction();
    $db->prepare("INSERT INTO profiles (user_id, full_name) SELECT :u, split_part(email, '@', 1) FROM users WHERE user_id = :u2
                  ON CONFLICT (user_id) DO NOTHING")->execute([':u' => $uid, ':u2' => $uid]);
    if ($updates) {
        $set = [];
        $params = [':u' => $uid];
        foreach ($updates as $f => $v) {
            $set[] = "$f = :$f";
            $params[":$f"] = $v ? 'true' : 'false';
        }
        $db->prepare("UPDATE profiles SET " . implode(', ', $set) . ", updated_at = CURRENT_TIMESTAMP WHERE user_id = :u")->execute($params);
    }
    $approved = 0;
    if (($updates['is_private'] ?? null) === false) {
        $a = $db->prepare("UPDATE connections SET status = 'accepted', accepted_at = NOW() WHERE addressee_user_id = :u AND status = 'pending'");
        $a->execute([':u' => $uid]);
        $approved = $a->rowCount();
    }
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_privacy: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update privacy settings.']);
    exit;
}

$out = current_settings($db, $uid, $role);
$out['enforced_public'] = $enforcedPublic;
$out['auto_approved'] = $approved;
$msg = $enforcedPublic ? 'Students are always public. Privacy remains Public.' : 'Privacy settings updated.';
if ($approved) {
    $msg .= " $approved pending follow request" . ($approved === 1 ? ' was' : 's were') . ' approved.';
}
echo json_encode(['success' => true, 'status' => 'success', 'message' => $msg, 'data' => $out]);
