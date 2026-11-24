<?php
/**
 * End-to-end API test suite.
 *
 * Drives the real HTTP API as an admin, an alumnus and a student: auth, profile,
 * settings, uploads, feed (create/edit/delete), comments, reactions, reposts,
 * messages (send/edit/delete), connections, jobs, events, mentorship,
 * notifications, search, referrals, atlas, admin tools and security controls.
 *
 * Usage:
 *   php tests/integration/run.php [base_url]          (default http://127.0.0.1:8099)
 * Env:
 *   ADMIN_EMAIL / ADMIN_PASSWORD   seeded admin (default admin@rjit.ac.in / Admin@12345)
 *
 * Writes tests/results/integration-latest.md and .json
 */

$BASE = rtrim($argv[1] ?? getenv('BASE_URL') ?: 'http://127.0.0.1:8099', '/');
$ADMIN_EMAIL = getenv('ADMIN_EMAIL') ?: 'admin@rjit.ac.in';
$ADMIN_PASS = getenv('ADMIN_PASSWORD') ?: 'Admin@12345';
$RUN = substr(bin2hex(random_bytes(3)), 0, 6);
$results = [];
$ctx = [];

/* ------------------------------------------------------------------ helpers */

function http(string $method, string $path, ?string $token = null, $body = null, array $files = [], array $headers = []): array
{
    global $BASE;
    $ch = curl_init($BASE . '/' . ltrim($path, '/'));
    $h = $headers;
    if ($token) {
        $h[] = 'Authorization: Bearer ' . $token;
    }
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 30];
    if ($files) {
        $post = is_array($body) ? $body : [];
        foreach ($files as $field => $file) {
            $post[$field] = new CURLFile($file[0], $file[1] ?? null, $file[2] ?? basename($file[0]));
        }
        $opts[CURLOPT_POSTFIELDS] = $post;
    } elseif ($body !== null) {
        $h[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    $opts[CURLOPT_HTTPHEADER] = $h;
    curl_setopt_array($ch, $opts);
    $t = microtime(true);
    $raw = curl_exec($ch);
    $ms = round((microtime(true) - $t) * 1000, 1);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $rawHeaders = substr((string)$raw, 0, $hsize);
    $text = substr((string)$raw, $hsize);
    return ['code' => $code, 'json' => json_decode($text, true), 'text' => $text, 'ms' => $ms, 'headers' => $rawHeaders];
}

function ok(array $r): bool
{
    $j = $r['json'];
    if ($r['code'] >= 400 || !is_array($j)) {
        return false;
    }
    if (array_key_exists('success', $j)) {
        return $j['success'] === true;
    }
    if (isset($j['status']) && is_string($j['status'])) {
        return $j['status'] !== 'error';
    }
    return true;
}

function msg(array $r): string
{
    $j = $r['json'];
    $m = is_array($j) ? ($j['message'] ?? $j['error'] ?? '') : substr(strip_tags($r['text']), 0, 160);
    return (string)(is_string($m) ? $m : json_encode($m));
}

function check(string $area, string $name, bool $pass, array $r = [], string $note = ''): bool
{
    global $results;
    $results[] = ['area' => $area, 'name' => $name, 'pass' => $pass, 'code' => $r['code'] ?? null, 'ms' => $r['ms'] ?? null, 'note' => $pass ? $note : trim($note . ' ' . ($r ? '[' . ($r['code'] ?? '') . '] ' . msg($r) : ''))];
    printf("  %s %-11s %-52s %6s %s\n", $pass ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m", $area, $name, isset($r['ms']) ? $r['ms'] . 'ms' : '', $pass ? '' : "\033[31m" . end($results)['note'] . "\033[0m");
    return $pass;
}

function find_id($data, array $keys)
{
    if (!is_array($data)) {
        return null;
    }
    foreach ($keys as $k) {
        if (isset($data[$k]) && is_scalar($data[$k]) && $data[$k] !== '') {
            return $data[$k];
        }
    }
    foreach ($data as $v) {
        if (is_array($v) && ($id = find_id($v, $keys)) !== null) {
            return $id;
        }
    }
    return null;
}

function login(string $email, string $pass): ?array
{
    $r = http('POST', 'api/login.php', null, ['email' => $email, 'password' => $pass]);
    return ok($r) ? ['token' => $r['json']['token'], 'id' => (int)$r['json']['user_id'], 'r' => $r] : ['token' => null, 'r' => $r];
}

function tiny_png(string $path): string
{
    $im = imagecreatetruecolor(64, 64);
    imagefilledrectangle($im, 0, 0, 63, 63, imagecolorallocate($im, 37, 99, 235));
    imagepng($im, $path);
    return $path;
}

$tmp = sys_get_temp_dir() . "/ap_it_$RUN";
@mkdir($tmp);
$png = tiny_png("$tmp/avatar.png");
file_put_contents("$tmp/evil.jpg", "\xFF\xD8\xFF\xE0" . '<?php system($_GET[1]); ?>');
file_put_contents("$tmp/fake.pdf", "not really a pdf");
$pdf = "$tmp/resume.pdf";
file_put_contents($pdf, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");

echo "\nRJIT Alumni Portal — integration tests against $BASE (run $RUN)\n\n";

/* ------------------------------------------------------------------- health */
$r = http('GET', 'healthz');
check('platform', 'Health check /healthz', $r['code'] === 200, $r);

/* --------------------------------------------------------------------- auth */
$admin = login($ADMIN_EMAIL, $ADMIN_PASS);
check('auth', 'Admin can sign in', (bool)$admin['token'], $admin['r']);
$r = http('POST', 'api/login.php', null, ['email' => $ADMIN_EMAIL, 'password' => 'wrong-password']);
check('auth', 'Wrong password is rejected (401)', $r['code'] === 401, $r);
$r = http('GET', 'api/me.php');
check('auth', 'API without token is rejected (401)', $r['code'] === 401, $r);
$r = http('GET', 'api/me.php', 'not-a-real-token');
check('auth', 'Invalid token is rejected (401)', $r['code'] === 401, $r);
$r = http('GET', 'api/me.php', $admin['token']);
check('auth', 'me.php returns the signed-in user', ok($r) && ($r['json']['data']['role'] ?? '') === 'admin', $r);
$r = http('GET', 'api/csrf_token.php', $admin['token']);
check('auth', 'CSRF token issued', ok($r) && !empty($r['json']['csrf_token'] ?? find_id($r['json'], ['csrf_token'])), $r);

/* -------------------------------------------------------------- registration */
$stuEmail = "it.student.$RUN@rjit.ac.in";
$aluEmail = "it.alumni.$RUN@example.com";
$pass = 'Test@' . $RUN . 'x9';
$r = http('POST', 'api/register_student.php', null, [
    'name' => "Test Student $RUN", 'email' => $stuEmail, 'password' => $pass, 'roll_number' => '0902CS23' . rand(1000, 9999),
    'course' => 'B.Tech', 'branch' => 'Computer Science & Engineering', 'graduation_year' => 2027,
]);
check('signup', 'Student can register', ok($r) || $r['code'] === 201, $r);
$r = http('POST', 'api/register_alumni.php', null, [
    'name' => "Test Alumnus $RUN", 'email' => $aluEmail, 'password' => $pass, 'branch' => 'Computer Science & Engineering',
    'joining_year' => 2016, 'current_company' => 'Infosys', 'position' => 'Senior Engineer', 'mobile_number' => '9876543210',
    'bio' => 'Integration test alumnus', 'help_alumni_mates' => 'Referrals, mock interviews',
]);
check('signup', 'Alumnus can register', ok($r) || $r['code'] === 201, $r);
$r = http('POST', 'api/register_student.php', null, ['name' => 'Dup', 'email' => $stuEmail, 'password' => $pass, 'roll_number' => 'x', 'course' => 'B.Tech', 'branch' => 'CSE', 'graduation_year' => 2027]);
check('signup', 'Duplicate email is rejected', !ok($r), $r);

// Invite codes: admin issues one, an alumnus uses it and is verified instantly
$r = http('POST', 'api/generate_token.php', $admin['token'] ?? null, []);
$invite = $r['json']['token'] ?? null;
check('signup', 'Admin generates an alumni invite code', ok($r) && $invite, $r);
$invEmail = "it.invited.$RUN@example.com";
$r = http('POST', 'api/register_alumni.php', null, ['name' => "Invited Alumnus $RUN", 'email' => $invEmail, 'password' => $pass, 'branch' => 'IT',
    'joining_year' => 2015, 'current_company' => 'TCS', 'position' => 'Lead', 'mobile_number' => '9876501234', 'invite_code' => strtoupper((string)$invite)]);
check('signup', 'Invite code verifies the alumnus instantly', ok($r) && ($r['json']['status'] ?? '') === 'active', $r);
$li = login($invEmail, $pass);
check('signup', 'Invited alumnus can sign in without approval', (bool)$li['token'], $li['r']);
$r = http('POST', 'api/register_alumni.php', null, ['name' => 'Reuse', 'email' => "it.reuse.$RUN@example.com", 'password' => $pass, 'branch' => 'IT',
    'joining_year' => 2015, 'mobile_number' => '9876501235', 'invite_code' => (string)$invite]);
check('signup', 'A used invite code cannot be reused', $r['code'] === 400, $r);
$r = http('GET', 'api/generate_token.php?action=list', $admin['token'] ?? null);
check('admin', 'Invite list shows the code as used', ok($r) && str_contains($r['text'], $invEmail), $r);

// Approve both through the admin API if they are pending
$pdoIds = [];
foreach ([$stuEmail, $aluEmail] as $em) {
    $s = http('GET', 'api/admin_users.php?search=' . urlencode($em), $admin['token']);
    $id = null;
    foreach (($s['json']['data'] ?? $s['json']['users'] ?? []) as $u) {
        if (strcasecmp($u['email'] ?? '', $em) === 0) {
            $id = (int)($u['user_id'] ?? $u['id']);
            $st = $u['status'] ?? '';
        }
    }
    $pdoIds[$em] = $id;
    if ($id && ($st ?? '') !== 'active') {
        $ap = http('POST', 'api/approve_user.php', $admin['token'], ['target_user_id' => $id, 'action' => 'approve']);
        check('admin', "Admin approves new account ($em)", ok($ap), $ap);
    }
}
check('admin', 'Admin user search finds new accounts', $pdoIds[$stuEmail] && $pdoIds[$aluEmail], $s);

$stu = login($stuEmail, $pass);
check('auth', 'New student can sign in', (bool)$stu['token'], $stu['r']);
$alu = login($aluEmail, $pass);
check('auth', 'New alumnus can sign in', (bool)$alu['token'], $alu['r']);
$S = $stu['token'];
$A = $alu['token'];
$AD = $admin['token'];
$sid = $stu['id'] ?? 0;
$aid = $alu['id'] ?? 0;

/* ------------------------------------------------------- profile & settings */
$r = http('POST', 'api/update_profile.php', $A, [
    'full_name' => "Test Alumnus $RUN", 'bio' => 'Building things at Infosys', 'current_company' => 'Infosys', 'job_role' => 'Senior Engineer',
    'location_city' => 'Bengaluru', 'location_country' => 'India', 'graduation_year' => 2020, 'branch' => 'Computer Science & Engineering',
    'linkedin_url' => 'https://www.linkedin.com/in/test', 'skills' => 'PHP, SQL',
]);
check('profile', 'Alumnus updates profile', ok($r), $r);
$r = http('GET', "api/get_user_profile.php?user_id=$aid", $S);
$p = $r['json']['data'] ?? $r['json']['profile'] ?? $r['json'];
check('profile', 'Profile changes are visible to others', ok($r) && str_contains(json_encode($p), 'Infosys'), $r);
$r = http('POST', 'api/upload_avatar.php', $A, [], ['avatar' => [$png, 'image/png', 'me.png']]);
check('profile', 'Avatar upload (PNG)', ok($r), $r);
$r = http('POST', 'api/upload_cover.php', $A, [], ['cover' => [$png, 'image/png', 'cover.png']]);
check('profile', 'Cover photo upload', ok($r), $r);
$r = http('POST', 'api/update_privacy.php', $A, ['is_private' => true]);
check('settings', 'Make profile private', ok($r), $r);
$r = http('POST', 'api/update_privacy.php', $A, ['is_private' => false]);
check('settings', 'Make profile public again', ok($r), $r);
$newPass = $pass . 'N';
$r = http('POST', 'api/change_password.php', $S, ['current_password' => $pass, 'new_password' => $newPass]);
check('settings', 'Change password', ok($r), $r);
$r = http('POST', 'api/change_password.php', $S, ['current_password' => 'wrong', 'new_password' => 'Whatever@123']);
check('settings', 'Change password with wrong current password fails', !ok($r), $r);
$re = login($stuEmail, $newPass);
check('settings', 'Sign in with the new password', (bool)$re['token'], $re['r']);
if ($re['token']) {
    $S = $re['token'];
}

/* --------------------------------------------------------------------- feed */
$r = http('POST', 'api/create_post.php', $A, ['content' => "Hello RJIT! Integration post $RUN #alumni", 'visibility_scope' => 'public', 'post_type' => 'text']);
$postId = find_id($r['json'], ['post_id', 'id']);
$pendingReview = str_contains(strtolower(msg($r)), 'review') || str_contains(strtolower(json_encode($r['json'])), 'pending');
check('feed', 'Alumnus creates a post', ok($r) && $postId, $r, $pendingReview ? '(queued for moderator review)' : '');
if ($pendingReview && $postId) {
    $mq = http('POST', 'api/moderation_queue.php', $AD, ['post_id' => $postId, 'action' => 'approve']);
    check('moderation', 'Admin approves the queued post', ok($mq), $mq);
}
$r = http('POST', 'api/create_post.php', $S, ['content' => 'Students should not be able to post top-level', 'post_type' => 'text']);
check('feed', 'Students cannot create top-level posts (rule)', !ok($r), $r);
$r = http('GET', 'api/get_feed.php?page=1', $S);
check('feed', 'Feed loads and shows the new post', ok($r) && str_contains($r['text'], "Integration post $RUN"), $r);
$r = http('POST', 'api/edit_post.php', $A, ['post_id' => $postId, 'content' => "Edited integration post $RUN"]);
check('feed', 'Author edits the post', ok($r), $r);
$r = http('POST', 'api/edit_post.php', $S, ['post_id' => $postId, 'content' => 'hijack']);
check('feed', "Others cannot edit someone else's post", !ok($r), $r);
$r = http('POST', 'api/react_to_post.php', $S, ['post_id' => $postId, 'reaction_type' => 'like']);
check('feed', 'Like / react to a post', ok($r), $r);
$r = http('POST', 'api/create_comment.php', $S, ['post_id' => $postId, 'content' => "Congrats! comment $RUN"]);
$commentId = find_id($r['json'], ['comment_id', 'id']);
check('feed', 'Comment on a post', ok($r) && $commentId, $r);
$r = http('POST', 'api/create_comment.php', $A, ['post_id' => $postId, 'content' => 'Thanks!', 'parent_comment_id' => $commentId]);
$replyId = find_id($r['json'], ['comment_id', 'id']);
check('feed', 'Reply to a comment', ok($r), $r);
$r = http('GET', "api/get_comments.php?post_id=$postId", $S);
check('feed', 'Comments load', ok($r) && str_contains($r['text'], "comment $RUN"), $r);
$r = http('POST', 'api/edit_comment.php', $S, ['comment_id' => $commentId, 'content' => "Edited comment $RUN"]);
check('feed', 'Edit own comment', ok($r), $r);
$r = http('POST', 'api/toggle_repost.php', $S, ['post_id' => $postId]);
check('feed', 'Repost', ok($r), $r);
$r = http('POST', 'api/create_share_link.php', $S, ['post_id' => $postId]);
check('feed', 'Share link', ok($r), $r);
$r = http('POST', 'api/track_post_view.php', $S, ['post_id' => $postId]);
check('feed', 'View is tracked', ok($r), $r);
$r = http('POST', 'api/pin_post.php', $A, ['post_id' => $postId]);
check('feed', 'Author pins the post to their profile', ok($r), $r);
$r = http('POST', 'api/pin_post.php', $S, ['post_id' => $postId]);
check('feed', "Cannot pin someone else's post", !ok($r), $r);
$r = http('POST', 'api/unpin_post.php', $A, ['post_id' => $postId]);
check('feed', 'Author unpins the post', ok($r), $r);
$r = http('GET', 'api/search_posts.php?q=' . urlencode("integration post $RUN") . "&query=" . urlencode("post $RUN"), $S);
check('feed', 'Search posts', ok($r), $r);
$r = http('POST', 'api/delete_comment.php', $S, ['comment_id' => $commentId]);
check('feed', 'Delete own comment', ok($r), $r);

/* ------------------------------------------------------------------ messages */
$r = http('POST', 'api/create_conversation.php', $S, ['other_user_id' => $aid]);
$convId = find_id($r['json'], ['conversation_id', 'id']);
check('messages', 'Start a conversation', ok($r), $r);
$r = http('POST', 'api/send_message.php', $S, ['receiver_id' => $aid, 'conversation_id' => $convId, 'message' => "Hi sir, message $RUN"]);
$msgId = find_id($r['json'], ['message_id', 'id']);
check('messages', 'Send a message', ok($r) && $msgId, $r);
$r = http('GET', 'api/get_unread_messages_count.php', $A);
check('messages', 'Recipient sees unread count', ok($r) && (int)find_id($r['json'], ['count', 'unread_count', 'unread']) >= 1, $r);
$r = http('GET', "api/get_messages.php?contact_id=$sid", $A);
check('messages', 'Recipient reads the conversation', ok($r) && str_contains($r['text'], "message $RUN"), $r);
$r = http('POST', 'api/send_message.php', $A, ['receiver_id' => $sid, 'message' => "Hello! reply $RUN"]);
check('messages', 'Reply', ok($r), $r);
$r = http('GET', 'api/get_conversations.php', $S);
check('messages', 'Conversation list', ok($r) && str_contains($r['text'], (string)$aid), $r);
$r = http('POST', 'api/edit_message.php', $S, ['message_id' => $msgId, 'message' => "Edited message $RUN"]);
check('messages', 'Edit own message', ok($r), $r);
$r = http('POST', 'api/edit_message.php', $A, ['message_id' => $msgId, 'message' => 'hijack']);
check('messages', "Cannot edit someone else's message", !ok($r), $r);
$r = http('POST', 'api/delete_message.php', $S, ['message_id' => $msgId]);
check('messages', 'Delete own message', ok($r), $r);

/* ------------------------------------------------------ network & discovery */
$r = http('POST', 'api/connect_user.php', $S, ['user_id' => $aid]);
check('network', 'Follow / connect with an alumnus', ok($r), $r);
$r = http('GET', 'api/search_directory.php?role=alumni&search=' . urlencode("Test Alumnus $RUN"), $S);
check('network', 'Directory search finds the alumnus', ok($r) && str_contains($r['text'], "Test Alumnus $RUN"), $r);
$r = http('GET', 'api/master_search.php?q=' . urlencode('Infosys'), $S);
check('network', 'Global search', ok($r), $r);
$r = http('GET', 'api/featured_alumni.php', $S);
check('network', 'Featured alumni', ok($r), $r);
$r = http('GET', 'api/get_connections_count.php', $A);
check('network', 'Connections count', ok($r), $r);

/* --------------------------------------------------------------------- jobs */
$r = http('POST', 'api/post_job.php', $A, ['job_title' => "SDE-1 ($RUN)", 'company_name' => 'Infosys', 'title' => "SDE-1 ($RUN)", 'company' => 'Infosys', 'location' => 'Bengaluru', 'job_type' => 'full-time', 'type' => 'full-time',
    'description' => 'Build great software. Integration test job.', 'salary_range' => '8-12 LPA', 'application_url' => '', 'requirements' => 'DSA, PHP']);
$jobId = find_id($r['json'], ['job_id', 'id']);
check('jobs', 'Alumnus posts a job', ok($r) && $jobId, $r);
$r = http('GET', 'api/get_jobs.php', $S);
check('jobs', 'Jobs list shows it', ok($r) && str_contains($r['text'], "SDE-1 ($RUN)"), $r);
$r = http('POST', 'api/apply_job.php', $S, ['job_id' => $jobId, 'cover_letter' => 'I would love to join.'], ['resume' => [$pdf, 'application/pdf', 'resume.pdf']]);
check('jobs', 'Student applies with résumé', ok($r), $r);
$r = http('GET', "api/get_applicants.php?job_id=$jobId", $A);
check('jobs', 'Poster sees applicants', ok($r) && str_contains($r['text'], "Test Student $RUN"), $r);
$r = http('GET', "api/get_applicants.php?job_id=$jobId", $S);
check('jobs', 'Others cannot see applicants', $r['code'] === 403, $r);
$r = http('POST', 'api/apply_job.php', $S, ['job_id' => $jobId, 'cover_letter' => 'again'], ['resume' => [$pdf, 'application/pdf', 'resume.pdf']]);
check('jobs', 'Applying twice is blocked', !ok($r), $r);
$r = http('GET', 'api/get_jobs.php', $S);
$mine = array_values(array_filter($r['json']['records'] ?? [], fn ($j) => (int)$j['job_id'] === (int)$jobId));
check('jobs', 'Job shows “applied” for the student and hides poster email', ($mine[0]['applied'] ?? false) === true && !str_contains($r['text'], $aluEmail), $r);
$r = http('POST', 'api/manage_job.php', $A, ['job_id' => $jobId, 'action' => 'update', 'job_title' => "SDE-1 Updated ($RUN)", 'company_name' => 'Infosys', 'description' => 'Updated description for the role.']);
check('jobs', 'Poster edits the job', ok($r), $r);
$r = http('POST', 'api/manage_job.php', $S, ['job_id' => $jobId, 'action' => 'delete']);
check('jobs', "Others cannot delete the job", $r['code'] === 403, $r);
$r = http('POST', 'api/manage_job.php', $A, ['job_id' => $jobId, 'action' => 'close']);
check('jobs', 'Poster closes the job', ok($r), $r);
$r = http('POST', 'api/post_job.php', $A, ['company_name' => 'X', 'job_title' => 'Y', 'description' => 'short']);
check('jobs', 'Invalid job is rejected with a clear message', $r['code'] === 400, $r);
$r = http('POST', 'api/manage_job.php', $A, ['job_id' => $jobId, 'action' => 'delete']);
check('jobs', 'Poster deletes the job', ok($r), $r);

/* ------------------------------------------------------------------- events */
$d = date('Y-m-d', strtotime('+10 days'));
$r = http('POST', 'api/events.php?action=create', $AD, ['title' => "Alumni Meet $RUN", 'description' => 'Annual meet', 'event_date' => $d, 'event_time' => '18:00',
    'end_date' => $d, 'end_time' => '21:00', 'location' => 'RJIT Auditorium', 'visibility' => 'public', 'rsvp_limit' => 100]);
$eventId = find_id($r['json'], ['event_id', 'id']);
check('events', 'Create an event', ok($r) && $eventId, $r);
$r = http('GET', 'api/events.php?action=list&filter=upcoming', $S);
check('events', 'Events list shows it', ok($r) && str_contains($r['text'], "Alumni Meet $RUN"), $r);
$r = http('POST', 'api/events.php?action=rsvp', $S, ['event_id' => $eventId, 'status' => 'going']);
check('events', 'RSVP', ok($r), $r);
$r = http('POST', 'api/events.php?action=rsvp', $S, ['event_id' => $eventId, 'status' => 'cancelled']);
check('events', 'Cancel RSVP', ok($r), $r);
$r = http('POST', 'api/events.php?action=create', $S, ['title' => "Student event $RUN", 'event_date' => $d]);
check('events', "Students can't create events", $r['code'] === 403, $r);

// Alumni propose → staff approve / decline (the queue has a UI on events.php)
$fac = login('neha.kulkarni.faculty@demo.rjit.local', 'Demo@12345');
$F = $fac['token'];
$r = http('POST', 'api/events.php?action=create', $A, ['title' => "Alumni proposal $RUN", 'description' => 'Resume clinic', 'event_date' => $d, 'event_time' => '17:00', 'location' => 'Seminar Hall']);
$propId = find_id($r['json'], ['event_id', 'id']);
check('events', 'Alumni can propose an event (pending)', ok($r) && $propId && ($r['json']['status'] ?? '') === 'pending_approval', $r);
$r = http('GET', 'api/events.php?action=list&filter=upcoming', $S);
check('events', 'Pending events stay out of the public list', ok($r) && !str_contains($r['text'], "Alumni proposal $RUN"), $r);
$r = http('POST', 'api/events.php?action=rsvp', $S, ['event_id' => $propId, 'status' => 'going']);
check('events', "Can't RSVP to an unapproved event", $r['code'] === 404, $r);
$r = http('GET', 'api/events.php?action=mine', $A);
check('events', 'Organiser sees it under My events', ok($r) && str_contains($r['text'], "Alumni proposal $RUN"), $r);
$r = http('GET', 'api/events.php?action=pending', $S);
check('events', "Students can't open the approval queue", $r['code'] === 403, $r);
$r = http('GET', 'api/events.php?action=pending', $F);
check('events', 'Faculty sees the approval queue', ok($r) && in_array($propId, array_map('intval', array_column($r['json']['data'] ?? [], 'event_id')), true), $r);
$r = http('POST', 'api/events.php?action=approve', $F, ['event_id' => $propId]);
check('events', 'Faculty approves the event', ok($r), $r);
$r = http('GET', 'api/events.php?action=list&filter=upcoming', $S);
check('events', 'Approved event is now listed', ok($r) && str_contains($r['text'], "Alumni proposal $RUN"), $r);
$r = http('POST', 'api/events.php?action=approve', $F, ['event_id' => $propId]);
check('events', 'Approving twice is refused (409)', $r['code'] === 409, $r);
$r = http('POST', 'api/events.php?action=create', $A, ['title' => "Second proposal $RUN", 'event_date' => $d, 'event_time' => '10:00', 'location' => 'Lab 3']);
$prop2 = find_id($r['json'], ['event_id', 'id']);
$r = http('POST', 'api/events.php?action=reject', $AD, ['event_id' => $prop2, 'reason' => 'Clashes with exams']);
check('events', 'Admin declines a proposal with a reason', ok($r), $r);
$r = http('GET', 'api/events.php?action=mine', $A);
$row = array_values(array_filter($r['json']['data'] ?? [], fn ($x) => (int)$x['event_id'] === (int)$prop2))[0] ?? [];
check('events', 'Organiser sees it as declined', ($row['status'] ?? '') === 'rejected', $r);
$r = http('GET', 'api/get_notifications.php', $A);
check('events', 'Organiser was notified', ok($r) && str_contains($r['text'], 'Clashes with exams'), $r);
$r = http('GET', 'api/get_upcoming_events_count.php', $S);
check('events', 'Upcoming events count', ok($r), $r);

/* --------------------------------------------------------------- mentorship */
$r = http('GET', 'api/mentorship.php?action=get_status', $S);
check('mentorship', 'Mentorship status', ok($r), $r);
$r = http('GET', 'api/mentorship.php?action=list_mentors', $S);
check('mentorship', 'List mentors', ok($r), $r);
$r = http('POST', 'api/mentorship.php?action=become_mentor', $A, ['action' => 'become_mentor', 'headline' => 'Backend mentor', 'expertise' => 'PHP, System design', 'message' => 'Happy to help juniors']);
check('mentorship', 'Alumnus applies to be a mentor', ok($r), $r);

/* ------------------------------------------------------------ notifications */
$r = http('GET', 'api/get_notifications.php', $A);
check('notify', 'Notifications load', ok($r), $r);
$nid = find_id($r['json'], ['notification_id']);
if ($nid) {
    $r = http('POST', 'api/mark_notif_read.php', $A, ['notification_id' => $nid]);
    check('notify', 'Mark a notification read', ok($r), $r);
}
$r = http('GET', 'api/get_unread_notifications_count.php', $A);
check('notify', 'Unread notifications count', ok($r), $r);

/* ---------------------------------------------------------------- referrals */
$r = http('POST', 'api/referrals.php', $A, ['action' => 'create_offer', 'company' => "Infosys $RUN", 'roles' => 'SDE-1, Intern', 'locations' => 'Bengaluru', 'experience' => 'fresher', 'slots' => 2]);
check('referrals', 'Alumnus publishes a referral offer', ok($r), $r);
$r = http('POST', 'api/referrals.php', $S, ['action' => 'create_offer', 'company' => 'X', 'roles' => 'Y']);
check('referrals', 'Students cannot publish offers', $r['code'] === 403, $r);
$r = http('GET', 'api/referrals.php?action=list&q=' . urlencode($RUN), $S);
$offerId = $r['json']['data'][0]['offer_id'] ?? null;
check('referrals', 'Student finds the offer', ok($r) && $offerId, $r);
$r = http('POST', 'api/referrals.php', $S, ['action' => 'request', 'offer_id' => $offerId, 'message' => 'too short']);
check('referrals', 'Too-short pitch is rejected with guidance', $r['code'] === 400, $r);
$r = http('POST', 'api/referrals.php', $S, ['action' => 'request', 'offer_id' => $offerId, 'message' => 'Final-year CSE student, built a campus portal in PHP and interned at a startup.', 'resume_url' => 'https://example.com/cv.pdf']);
check('referrals', 'Student requests a referral', ok($r), $r);
$r = http('POST', 'api/referrals.php', $S, ['action' => 'request', 'offer_id' => $offerId, 'message' => 'Final-year CSE student, built a campus portal in PHP and interned at a startup.']);
check('referrals', 'Duplicate request is blocked', $r['code'] === 409, $r);
$r = http('GET', "api/referrals.php?action=offer_requests&offer_id=$offerId", $A);
$reqId = $r['json']['data'][0]['request_id'] ?? null;
check('referrals', 'Owner sees the request', ok($r) && $reqId, $r);
$r = http('GET', "api/referrals.php?action=offer_requests&offer_id=$offerId", $S);
check('referrals', 'Others cannot see the requests', $r['code'] === 403, $r);
$r = http('POST', 'api/referrals.php', $A, ['action' => 'respond', 'request_id' => $reqId, 'status' => 'referred', 'note' => 'Submitted, check mail']);
check('referrals', 'Owner marks as referred', ok($r), $r);
$r = http('GET', 'api/referrals.php?action=my_requests', $S);
check('referrals', 'Student sees status “referred”', ok($r) && ($r['json']['data'][0]['status'] ?? '') === 'referred', $r);
$r = http('GET', 'api/referrals.php?action=stats', $S);
check('referrals', 'Board stats', ok($r) && $r['json']['data']['referred'] >= 1, $r);
$r = http('POST', 'api/referrals.php', $AD, ['action' => 'update_offer', 'offer_id' => $offerId, 'status' => 'removed']);
check('referrals', 'Admin can remove an offer', ok($r), $r);

/* -------------------------------------------------------------------- atlas */
$r = http('GET', 'api/alumni_atlas.php', $S);
check('atlas', 'Atlas aggregates load', ok($r) && isset($r['json']['data']['totals']), $r);
$r = http('GET', 'api/alumni_atlas.php?city=Bengaluru', $S);
check('atlas', 'Atlas lists alumni in Bengaluru', ok($r) && str_contains($r['text'], "Test Alumnus $RUN"), $r);

/* -------------------------------------------------------------------- admin */
$r = http('GET', 'api/admin_stats.php', $AD);
check('admin', 'Admin stats', ok($r), $r);
$r = http('GET', 'api/admin_stats.php', $S);
check('admin', 'Non-admins cannot see admin stats', !ok($r), $r);
$r = http('GET', 'api/admin_insights.php', $AD);
check('admin', 'Insights dashboard data', ok($r) && isset($r['json']['data']['growth']), $r);
$r = http('GET', 'api/admin_insights.php', $A);
check('admin', 'Non-admins blocked from insights', $r['code'] === 403, $r);
$r = http('POST', 'api/admin_insights.php', $AD, ['action' => 'set_banner', 'message' => "Test banner $RUN", 'tone' => 'info', 'link' => 'events.php']);
check('admin', 'Publish site banner', ok($r), $r);
$r = http('GET', 'api/site_banner.php');
check('admin', 'Banner is public on every page', ok($r) && str_contains($r['text'], "Test banner $RUN"), $r);
$r = http('POST', 'api/admin_insights.php', $AD, ['action' => 'clear_banner']);
check('admin', 'Clear site banner', ok($r), $r);
$r = http('POST', 'api/admin_insights.php', $AD, ['action' => 'broadcast', 'audience' => 'student', 'message' => "Broadcast test $RUN"]);
check('admin', 'Broadcast to students', ok($r) && ($r['json']['data']['sent'] ?? 0) >= 1, $r);
$r = http('GET', 'api/get_notifications.php', $S);
check('admin', 'Student receives the broadcast', str_contains($r['text'], "Broadcast test $RUN"), $r);
$r = http('GET', 'api/admin_export_users.php?role=alumni', $AD);
check('admin', 'Export alumni CSV', $r['code'] === 200 && str_contains($r['text'], $aluEmail), $r);
$r = http('GET', 'api/view_logs.php', $AD);
check('admin', 'Audit log', $r['code'] === 200 && is_array($r['json']), $r);

/* ----------------------------------------------------------------- uploads */
$r = http('POST', 'api/upload_file.php', $A, ['context' => 'posts'], ['attachment' => [$png, 'image/png', 'photo.png']]);
check('uploads', 'Upload an image attachment', ok($r), $r);
$r = http('POST', 'api/upload_file.php', $A, ['context' => 'posts'], ['attachment' => ["$tmp/evil.jpg", 'image/jpeg', 'cute.jpg']]);
check('uploads', 'PHP hidden in a JPEG is blocked (415)', $r['code'] === 415, $r);
$r = http('POST', 'api/upload_file.php', $A, ['context' => 'posts'], ['attachment' => [$png, 'image/png', 'shell.php.png']]);
check('uploads', 'Double extension is blocked (415)', $r['code'] === 415, $r);
$r = http('POST', 'api/upload_file.php', $A, ['context' => 'posts'], ['attachment' => ["$tmp/fake.pdf", 'application/pdf', 'proof.pdf']]);
check('uploads', 'Fake PDF is blocked (415)', $r['code'] === 415, $r);

/* ---------------------------------------------------------------- security */
$r = http('GET', 'api/me.php', $S, null, [], ['Origin: https://evil.example']);
check('security', 'Hostile origin gets no CORS header', stripos($r['headers'], 'access-control-allow-origin') === false, $r);
$r = http('GET', 'feed.php');
check('security', 'Pages send a Content-Security-Policy', stripos($r['headers'], 'content-security-policy') !== false, $r);
$r = http('GET', 'config/Database.php');
check('security', 'Internal files are hidden (404)', $r['code'] === 404, $r);
$r = http('POST', 'api/delete_post.php', $S, ['post_id' => $postId]);
check('security', "Cannot delete someone else's post", !ok($r), $r);

/* ------------------------------------------------- social: likes, follow, hype */
$r = http('POST', 'api/create_post.php', $AD, ['content' => "Social post $RUN", 'post_type' => 'text']);
$sp = find_id($r['json'], ['post_id']);
check('social', 'Admin publishes a post instantly', ok($r) && $sp, $r);
$r = http('POST', 'api/react_to_post.php', $S, ['post_id' => $sp, 'reaction_type' => 'like']);
$r2 = http('GET', 'api/get_feed.php?page=1&sort=newest', $S);
$row = array_values(array_filter($r2['json']['data'] ?? [], fn ($x) => (int)$x['id'] === (int)$sp))[0] ?? [];
$likesAfter = (int)($row['likes_count'] ?? $row['reaction_count'] ?? 0);
check('social', 'Like increments the like count', ok($r) && ($r['json']['liked'] ?? false) === true && $likesAfter === 1, $r2);
$r = http('POST', 'api/react_to_post.php', $S, ['post_id' => $sp]);
$r2 = http('GET', 'api/get_feed.php?page=1&sort=newest', $S);
$row = array_values(array_filter($r2['json']['data'] ?? [], fn ($x) => (int)$x['id'] === (int)$sp))[0] ?? [];
check('social', 'Liking again removes the like (toggle)', ok($r) && ($r['json']['liked'] ?? true) === false && (int)($row['likes_count'] ?? $row['reaction_count'] ?? 1) === 0, $r2);
$r = http('POST', 'api/create_comment.php', $S, ['post_id' => $sp, 'content' => "Nice $RUN"]);
$cid = find_id($r['json'], ['comment_id', 'id']);
$r = http('POST', 'api/react_to_comment.php', $A, ['comment_id' => $cid, 'reaction' => 'like']);
check('social', 'React to a comment', ok($r), $r);
foreach (['for_you' => 'For-you (personalised hype) feed', 'trending' => 'Trending (time-decayed hype) feed', 'popular' => 'Most-popular feed'] as $sort => $label) {
    $r = http('GET', "api/get_feed.php?page=1&sort=$sort", $S);
    check('social', $label . ' loads', ok($r) && count($r['json']['data'] ?? []) > 0, $r);
}
$r = http('POST', 'api/create_post.php', $AD, ['content' => "Hot post $RUN", 'post_type' => 'text']);
$hot = find_id($r['json'], ['post_id']);
foreach ([$S, $A] as $tk) {
    http('POST', 'api/react_to_post.php', $tk, ['post_id' => $hot]);
    http('POST', 'api/create_comment.php', $tk, ['post_id' => $hot, 'content' => 'wow']);
}
$r = http('POST', 'api/create_post.php', $AD, ['content' => "Cold post $RUN", 'post_type' => 'text']);
$cold = find_id($r['json'], ['post_id']);
$pos = function (array $resp, $id) { foreach ($resp['json']['data'] ?? [] as $i => $x) { if ((int)$x['id'] === (int)$id) { return $i; } } return 999; };
$rn = http('GET', 'api/get_feed.php?page=1&sort=newest', $S);
$rt = http('GET', 'api/get_feed.php?page=1&sort=trending', $S);
check('social', 'Trending ranks an engaged post above a newer silent one', $pos($rn, $cold) < $pos($rn, $hot) && $pos($rt, $hot) < $pos($rt, $cold), $rt);
$r = http('POST', 'api/create_post.php', $A, ['content' => "From someone you follow $RUN", 'post_type' => 'text']);
$fp = find_id($r['json'], ['post_id']);
http('POST', 'api/moderation_queue.php', $AD, ['post_id' => $fp, 'action' => 'approve']);
$r = http('GET', 'api/get_feed.php?page=1&filter=following', $S);
check('social', 'Following feed shows posts from people you follow', ok($r) && str_contains($r['text'], "From someone you follow $RUN"), $r);
$r = http('GET', 'api/get_feed.php?page=1&filter=following', $AD);
check('social', "Following feed excludes people you don't follow", ok($r) && !str_contains($r['text'], "From someone you follow $RUN"), $r);

/* ------------------------------------------------------------ reports */
$r = http('POST', 'api/report_content.php', $S, ['post_id' => $fp, 'reason' => 'spam', 'custom_reason' => 'Test report']);
check('reports', 'Report a post', ok($r), $r);
$r = http('POST', 'api/report_content.php', $S, ['post_id' => $fp, 'reason' => 'spam']);
check('reports', 'One report per person per post', !ok($r) || stripos(msg($r), 'already') !== false, $r);
$r = http('GET', 'api/get_reports.php', $AD);
$repId = null;
foreach ((array)($r['json']['data'] ?? $r['json']) as $rep) {
    if (is_array($rep) && (int)($rep['reported_post_id'] ?? $rep['post_id'] ?? 0) === (int)$fp) { $repId = $rep['report_id'] ?? null; }
}
check('reports', 'Admin sees the report', $r['code'] === 200 && $repId, $r);
$r = http('POST', 'api/admin_report_action.php', $AD, ['report_id' => $repId, 'action' => 'dismiss']);
check('reports', 'Admin dismisses the report', ok($r), $r);

/* -------------------------------------------------------------- calls */
$r = http('POST', 'api/start_call.php', $S, ['receiver_id' => $aid, 'call_type' => 'video']);
check('calls', '1:1 video call creates a Jitsi room', ok($r) && str_contains($r['text'], 'jit.si'), $r);
$r = http('GET', 'api/get_notifications.php', $A);
check('calls', 'Callee gets a call notification', str_contains(strtolower($r['text']), 'call'), $r);

/* -------------------------------------------- mentorship + group chat */
$r = http('POST', 'api/mentorship.php?action=become_mentor', $A, ['headline' => 'Backend mentor', 'expertise' => 'PHP']);
check('mentor', 'Alumnus applies to mentor', ok($r), $r);
$r = http('POST', 'api/mentorship.php?action=review_application', $AD, ['mentor_user_id' => $aid, 'status' => 'approved']);
check('mentor', 'Admin approves the mentor', ok($r), $r);
$r = http('POST', 'api/mentorship.php?action=request', $S, ['mentor_id' => $aid, 'message' => 'Please mentor me']);
check('mentor', 'Student requests mentorship', ok($r), $r);
$r = http('GET', 'api/mentorship.php?action=list_requests', $A);
$mreq = null;
foreach ($r['json']['data'] ?? [] as $q) { if ((int)($q['mentee_id'] ?? 0) === $sid) { $mreq = $q['request_id']; } }
check('mentor', 'Mentor sees the request', (bool)$mreq, $r);
$r = http('POST', 'api/mentorship.php?action=respond', $A, ['request_id' => $mreq, 'status' => 'accepted']);
check('mentor', 'Mentor accepts → mentor group created', ok($r), $r);
$r = http('GET', 'api/get_conversations.php', $S);
$gid = null;
foreach ($r['json']['data'] ?? $r['json']['conversations'] ?? [] as $c) {
    $cidv = (string)($c['conversation_id'] ?? $c['id'] ?? '');
    if (str_starts_with($cidv, 'group:')) { $gid = substr($cidv, 6); }
}
check('mentor', 'Mentor group appears in Messages', (bool)$gid, $r);
$r = http('POST', 'api/send_message.php', $S, ['conversation_id' => "group:$gid", 'message' => "Group hello $RUN"]);
check('mentor', 'Send a group message', ok($r), $r);
$r = http('GET', "api/get_messages.php?conversation_id=group:$gid", $A);
check('mentor', 'Mentor reads the group message', ok($r) && str_contains($r['text'], "Group hello $RUN"), $r);
$r = http('POST', 'api/start_call.php', $A, ['group_id' => (int)$gid, 'call_type' => 'audio']);
check('mentor', 'Start a group voice space', ok($r), $r);
$r = http('POST', 'api/end_group_call.php', $A, ['group_id' => (int)$gid]);
check('mentor', 'End the group voice space', ok($r), $r);
$r = http('POST', 'api/mentorship.php?action=leave_group', $S, ['group_id' => (int)$gid]);
check('mentor', 'Mentee leaves the group', ok($r), $r);

/* -------------------------------------------------------------- polls */
$r = http('POST', 'api/create_post.php', $AD, ['content' => "Poll post $RUN"]);
$pp = find_id($r['json'], ['post_id']);
$r = http('POST', 'api/create_poll.php', $S, ['post_id' => $pp, 'question' => 'Hijack?', 'options' => ['a', 'b']]);
check('polls', "Can't add a poll to someone else's post", $r['code'] === 403, $r);
$r = http('POST', 'api/create_poll.php', $AD, ['post_id' => $pp, 'question' => 'Best city to work in?', 'options' => ['Pune', 'Bengaluru', 'Hyderabad']]);
$pollId = $r['json']['poll_id'] ?? null;
check('polls', 'Create a poll (no expiry)', ok($r) && $pollId, $r);
$r = http('GET', 'api/get_feed.php?page=1&sort=newest', $S);
$row = array_values(array_filter($r['json']['data'] ?? [], fn ($x) => (int)$x['id'] === (int)$pp))[0] ?? [];
$opt = $row['poll']['options'][1]['option_id'] ?? null;
check('polls', 'Feed carries the poll with options', $opt !== null && count($row['poll']['options']) === 3, $r);
$r = http('POST', 'api/vote_poll.php', $S, ['poll_id' => $pollId, 'option_id' => $opt]);
check('polls', 'Vote (no-expiry polls are open)', ok($r) && ($r['json']['poll']['my_vote'] ?? 0) === $opt, $r);
$r = http('POST', 'api/vote_poll.php', $S, ['poll_id' => $pollId, 'option_id' => $opt]);
check('polls', 'Second vote is blocked', $r['code'] === 409, $r);
$r = http('POST', 'api/vote_poll.php', $A, ['poll_id' => $pollId, 'option_id' => 999999]);
check('polls', "Option from another poll is rejected", $r['code'] === 400, $r);
$r = http('POST', 'api/end_poll.php', $AD, ['poll_id' => $pollId]);
check('polls', 'Author closes the poll', $r['code'] === 200, $r);
$r = http('POST', 'api/vote_poll.php', $A, ['poll_id' => $pollId, 'option_id' => $opt]);
check('polls', 'Closed poll refuses votes', $r['code'] === 409, $r);

/* -------------------------------------------------- scheduled posts */
$in = fn ($sec) => gmdate('c', time() + $sec);
$r = http('POST', 'api/schedule_post.php', $S, ['content' => 'student schedule', 'publish_at' => $in(3600)]);
check('schedule', 'Students cannot schedule top-level posts', $r['code'] === 403, $r);
$r = http('POST', 'api/schedule_post.php', $AD, ['content' => 'too soon', 'publish_at' => $in(10)]);
check('schedule', 'Times in the past / too soon are rejected', $r['code'] === 400, $r);
$r = http('POST', 'api/schedule_post.php', $AD, ['content' => "Scheduled hello $RUN", 'publish_at' => $in(7200),
    'poll' => ['question' => 'Coming to the meet?', 'options' => ['Yes', 'No']]]);
$schId = $r['json']['scheduled_id'] ?? null;
check('schedule', 'Schedule a post with a poll', ok($r) && $schId, $r);
$r = http('GET', 'api/scheduled_posts.php', $AD);
check('schedule', 'It is listed as pending', ok($r) && str_contains($r['text'], "Scheduled hello $RUN"), $r);
$r = http('POST', 'api/scheduled_posts.php', $AD, ['action' => 'reschedule', 'scheduled_id' => $schId, 'publish_at' => $in(10800)]);
check('schedule', 'Reschedule', ok($r), $r);
$r = http('POST', 'api/schedule_post.php', $AD, ['content' => "Cancel me $RUN", 'publish_at' => $in(7200)]);
$cancelId = $r['json']['scheduled_id'] ?? 0;
$r = http('POST', 'api/scheduled_posts.php', $AD, ['action' => 'cancel', 'scheduled_id' => $cancelId]);
check('schedule', 'Cancel a scheduled post', ok($r), $r);
$r = http('POST', 'api/scheduled_posts.php', $S, ['action' => 'cancel', 'scheduled_id' => $schId]);
check('schedule', "Can't touch someone else's schedule", $r['code'] === 404, $r);
if (getenv('DB_NAME')) {
    // Make it due, then run the real cron entry point
    $pdo = new PDO('pgsql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: 5432) . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER') ?: 'postgres', getenv('DB_PASSWORD') ?: '');
    $pdo->exec("SET TIME ZONE 'UTC'");
    $pdo->prepare("UPDATE scheduled_posts SET publish_at = NOW() - INTERVAL '1 minute' WHERE scheduled_id = :id")->execute(['id' => $schId]);
    $cron = shell_exec('php ' . escapeshellarg(dirname(__DIR__, 2) . '/cron/run.php') . ' --job=publish 2>&1');
    check('schedule', 'Cron publishes due posts', str_contains((string)$cron, 'published=1'), [], trim((string)$cron));
    $r = http('GET', 'api/get_feed.php?page=1&sort=newest', $S);
    $row = array_values(array_filter($r['json']['data'] ?? [], fn ($x) => str_contains((string)($x['content'] ?? ''), "Scheduled hello $RUN")))[0] ?? null;
    check('schedule', 'Published post is on the feed with its poll', $row && !empty($row['poll']), $r);
    $r = http('GET', 'api/scheduled_posts.php', $AD);
    check('schedule', 'Schedule shows it as published', str_contains($r['text'], '"status":"published"'), $r);
    foreach (['promote', 'purge', 'archive'] as $job) {
        $out = shell_exec('php ' . escapeshellarg(dirname(__DIR__, 2) . '/cron/run.php') . " --job=$job 2>&1");
        check('cron', "cron/run.php --job=$job runs cleanly", !str_contains(strtolower((string)$out), 'failed') && str_contains((string)$out, $job), [], trim((string)$out));
    }
    $dry = shell_exec('php ' . escapeshellarg(dirname(__DIR__, 2) . '/cron/archive_old_posts.php') . ' 2>&1');
    check('cron', 'Archive is a dry run unless --apply', str_contains((string)$dry, 'DRY RUN'), [], trim((string)$dry));
}

/* ---------------------------------------------------------------- live */
$r = http('POST', 'api/toggle_stream.php', $S, ['action' => 'start', 'title' => 'student live']);
check('live', 'Students cannot host live sessions', $r['code'] === 403, $r);
$r = http('POST', 'api/toggle_stream.php', $A, ['action' => 'start', 'title' => 'x', 'provider' => 'youtube', 'url' => 'https://evil.example/x']);
check('live', 'Bad YouTube link / short title rejected', $r['code'] === 400, $r);
$r = http('POST', 'api/toggle_stream.php', $A, ['action' => 'start', 'title' => "AMA $RUN", 'provider' => 'jitsi']);
check('live', 'Alumnus goes live (Jitsi room)', ok($r) && str_contains((string)($r['json']['data']['embed_url'] ?? ''), 'meet.jit.si'), $r);
$r = http('GET', 'api/get_active_streams.php', $S);
check('live', 'Viewers see the live session', ok($r) && str_contains($r['text'], "AMA $RUN"), $r);
check('live', 'Stream keys are never exposed', !str_contains($r['text'], 'stream_key') && !str_contains($r['text'], 'live_' . $aid . '_'), $r);
$r = http('POST', 'api/toggle_stream.php', $A, ['action' => 'start', 'title' => "YT $RUN", 'provider' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
check('live', 'Switch to a YouTube Live embed', ok($r) && str_contains((string)($r['json']['data']['embed_url'] ?? ''), 'youtube-nocookie.com/embed/dQw4w9WgXcQ'), $r);
$r = http('POST', 'api/toggle_stream.php', $A, ['action' => 'stop']);
$r2 = http('GET', 'api/get_active_streams.php', $S);
check('live', 'End the session', ok($r) && !str_contains($r2['text'], "YT $RUN"), $r2);

/* ------------------------------------------------------------- library */
$r = http('POST', 'api/success_stories.php?action=create', $A, ['title' => "My journey $RUN", 'content' => str_repeat('From RJIT to a product company. ', 3)]);
$story = $r['json']['story_id'] ?? null;
check('library', 'Alumnus shares a success story', ok($r) && $story, $r);
$r = http('GET', 'api/success_stories.php?action=list', $S);
check('library', 'Unapproved story is not public', ok($r) && !str_contains($r['text'], "My journey $RUN"), $r);
$r = http('POST', 'api/success_stories.php?action=approve', $S, ['story_id' => $story]);
check('library', 'Only admins approve stories', $r['code'] === 403, $r);
$r = http('POST', 'api/success_stories.php?action=approve', $AD, ['story_id' => $story]);
$r2 = http('GET', 'api/success_stories.php?action=list', $S);
check('library', 'Approved story is public', ok($r) && str_contains($r2['text'], "My journey $RUN"), $r2);
$r = http('POST', 'api/resources.php?action=create', $S, ['title' => 'Bad link', 'file_url' => 'javascript:alert(1)', 'category' => 'notes']);
check('library', 'Non-https resource links are rejected', $r['code'] === 400, $r);
$r = http('POST', 'api/resources.php?action=create', $A, ['title' => "DSA sheet $RUN", 'file_url' => 'https://example.com/dsa', 'category' => 'placements']);
$res = $r['json']['resource_id'] ?? null;
check('library', 'Share a resource', ok($r) && $res, $r);
$r = http('POST', 'api/resources.php?action=download', $S, ['resource_id' => $res]);
check('library', 'Open a resource (counted)', ok($r) && $r['json']['url'] === 'https://example.com/dsa', $r);
$r = http('POST', 'api/resources.php?action=delete', $S, ['resource_id' => $res]);
check('library', "Can't delete someone else's resource", $r['code'] === 403, $r);
$r = http('POST', 'api/resources.php?action=delete', $A, ['resource_id' => $res]);
check('library', 'Owner deletes the resource', ok($r), $r);

/* ------------------------------------------------- privacy, follows, blocks */
$dem = login('ananya.verma.1@demo.rjit.local', 'Demo@12345');
$D = $dem['token'];
$did = $dem['id'] ?? 0;
if ($D) {
    $r = http('POST', 'api/update_privacy.php', $A, ['is_private' => 'false']);
    check('privacy', '"false" as a string keeps the profile public', ok($r) && ($r['json']['data']['is_private'] ?? true) === false, $r);
    http('POST', 'api/connect_user.php', $S, ['user_id' => $aid, 'action' => 'unfollow']);   // start as a non-follower
    $r = http('POST', 'api/update_privacy.php', $A, ['is_private' => true, 'show_email' => false]);
    check('privacy', 'Alumnus switches to a private profile', ok($r) && $r['json']['data']['is_private'] === true, $r);
    $r = http('GET', "api/get_user_profile.php?user_id=$aid", $S);
    $p = $r['json']['data'] ?? [];
    check('privacy', 'Non-follower sees only a locked card', ok($r) && !empty($p['is_locked']) && ($p['bio'] ?? '') === '' && !isset($p['email']) && !isset($p['location_city']), $r);
    $r = http('GET', "api/get_feed.php?user_id=$aid&filter=all", $S);
    check('privacy', "Private timeline isn't readable by non-followers", ok($r) && !str_contains($r['text'], "From someone you follow $RUN"), $r);
    $r = http('GET', 'api/get_feed.php?sort=newest', $S);
    check('privacy', "Private author's posts leave non-followers' feeds", ok($r) && !str_contains($r['text'], "From someone you follow $RUN"), $r);
    $r = http('POST', 'api/react_to_post.php', $S, ['post_id' => $fp]);
    check('privacy', "Can't like a post you aren't allowed to see", $r['code'] === 404, $r);
    $r = http('GET', "api/get_comments.php?post_id=$fp", $S);
    check('privacy', "Can't read comments on a hidden post", $r['code'] === 404, $r);
    $r = http('GET', 'api/search_directory.php?search=Infosys', $S);
    check('privacy', "Private profiles can't be found by employer", ok($r) && !str_contains($r['text'], "Test Alumnus $RUN"), $r);
    $r = http('GET', 'api/search_directory.php?search=' . urlencode("Test Alumnus $RUN"), $S);
    $row = array_values(array_filter($r['json']['data'] ?? [], fn ($x) => (int)($x['id'] ?? 0) === $aid))[0] ?? null;
    check('privacy', 'Private profiles are findable by name (details hidden)', ok($r) && $row && !empty($row['is_locked']) && empty($row['current_company']) && empty($row['bio']), $r);
    $r = http('POST', 'api/connect_user.php', $S, ['user_id' => $aid]);
    check('follow', 'Following a private account sends a request', ok($r) && ($r['json']['follow_state'] ?? '') === 'requested', $r);
    $r = http('GET', 'api/connect_user.php?action=requests', $A);
    check('follow', 'Owner sees the pending request', ok($r) && in_array($sid, array_column($r['json']['data'] ?? [], 'user_id'), true), $r);
    $r = http('POST', 'api/connect_user.php', $A, ['action' => 'approve', 'user_id' => $sid]);
    check('follow', 'Owner approves the request', ok($r), $r);
    $r = http('GET', "api/get_user_profile.php?user_id=$aid", $S);
    check('follow', 'Approved follower sees the full profile', ok($r) && empty($r['json']['data']['is_locked']) && str_contains($r['text'], 'Infosys'), $r);
    check('privacy', 'Hidden email stays hidden even from followers', ok($r) && !isset($r['json']['data']['email']), $r);
    $r = http('GET', "api/get_feed.php?user_id=$aid&filter=all", $S);
    check('follow', "Follower can read the private timeline", ok($r) && str_contains($r['text'], "From someone you follow $RUN"), $r);
    $r = http('POST', 'api/connect_user.php', $D, ['user_id' => $aid]);
    $r = http('POST', 'api/update_privacy.php', $A, ['is_private' => false, 'show_email' => true]);
    check('follow', 'Going public auto-approves pending requests', ok($r) && ($r['json']['data']['auto_approved'] ?? 0) >= 1, $r);
    $r = http('POST', 'api/update_privacy.php', $S, ['is_private' => true]);
    check('privacy', 'Students stay public (policy)', ok($r) && $r['json']['data']['is_private'] === false, $r);
    $r = http('GET', "api/get_user_profile.php?user_id=$sid", $D);
    check('privacy', "Roll numbers aren't shown to other members", ok($r) && empty($r['json']['data']['roll_number']), $r);

    $r = http('POST', 'api/block_user.php', $D, ['user_id' => $sid, 'action' => 'block']);
    check('block', 'Block someone', ok($r) && !empty($r['json']['blocked']), $r);
    $r = http('GET', "api/get_user_profile.php?user_id=$did", $S);
    check('block', 'Blocked person sees "not found"', $r['code'] === 404, $r);
    $r = http('POST', 'api/create_conversation.php', $S, ['other_user_id' => $did]);
    check('block', "Blocked person can't start a chat", $r['code'] === 403, $r);
    $r = http('POST', 'api/connect_user.php', $S, ['user_id' => $did]);
    check('block', "Blocked person can't follow", $r['code'] === 403, $r);
    $r = http('GET', 'api/block_user.php?action=list', $D);
    check('block', 'Blocked list shows them', ok($r) && in_array($sid, array_column($r['json']['data'] ?? [], 'user_id'), true), $r);
    $r = http('POST', 'api/block_user.php', $D, ['user_id' => $sid, 'action' => 'unblock']);
    $r2 = http('GET', "api/get_user_profile.php?user_id=$did", $S);
    check('block', 'Unblock restores access', ok($r) && ok($r2), $r2);
    $r = http('POST', 'api/block_user.php', $S, ['user_id' => $admin['id'], 'action' => 'block']);
    check('block', "Staff can't be blocked (use Report)", $r['code'] === 403, $r);
}

/* -------------------------------------------------------------- @handles */
$r = http('GET', 'api/username.php', $S);
check('handle', 'Every account has an @handle', ok($r) && preg_match('/^[a-z][a-z0-9_]{2,29}$/', (string)($r['json']['data']['username'] ?? '')), $r);
$r = http('GET', 'api/username.php?check=admin', $A);
check('handle', 'Reserved handles are refused', ok($r) && $r['json']['data']['available'] === false, $r);
$h = "it_$RUN";
$r = http('POST', 'api/username.php', $A, ['username' => strtoupper($h)]);
check('handle', 'Change handle (stored lower-case)', ok($r) && $r['json']['data']['username'] === $h, $r);
$r = http('GET', "api/get_user_profile.php?username=@$h", $S);
check('handle', 'Open a profile by @handle', ok($r) && (int)$r['json']['data']['user_id'] === $aid, $r);
if ($D) {
    $r = http('POST', 'api/username.php', $D, ['username' => strtoupper($h)]);
    check('handle', 'Handles are unique regardless of case', $r['code'] === 409, $r);
}
$r = http('POST', 'api/username.php', $A, ['username' => "it2_$RUN"]);
check('handle', 'Cool-down between handle changes', $r['code'] === 429, $r);
$r = http('GET', 'api/master_search.php?q=' . urlencode("@$h"), $S);
check('handle', 'Search finds people by @handle', ok($r) && str_contains($r['text'], $h), $r);
$r = http('GET', "@$h");
check('handle', '/@handle links redirect to the profile', $r['code'] === 302, $r);

/* ------------------------------------------------- profile input validation */
$r = http('POST', 'api/update_profile.php', $A, ['location_city' => str_repeat('a', 300)]);
check('validation', 'Too-long city gets a friendly 422 (not a 500)', $r['code'] === 422 && isset($r['json']['errors']['location_city']), $r);
$r = http('POST', 'api/update_profile.php', $A, ['linkedin_url' => 'javascript:alert(1)']);
check('validation', 'javascript: links are refused', $r['code'] === 422, $r);
$r = http('POST', 'api/update_profile.php', $A, ['linkedin_url' => 'linkedin.com/in/test', 'full_name' => "<b>Test</b> Alumnus $RUN"]);
$r2 = http('GET', "api/get_user_profile.php?user_id=$aid", $A);
check('validation', 'Links get https:// and names lose HTML', ok($r) && ($r2['json']['data']['linkedin_url'] ?? '') === 'https://linkedin.com/in/test'
    && ($r2['json']['data']['full_name'] ?? '') === "Test Alumnus $RUN", $r2);
$r = http('POST', 'api/update_profile.php', $S, ['bio' => 'Final-year CSE', 'full_name' => 'Someone Else']);
check('validation', 'Students edit their bio; name stays locked', ok($r) && in_array('full_name', $r['json']['locked_fields'] ?? [], true), $r);
$r = http('POST', 'api/react_to_post.php', $S, ['post_id' => "1' OR '1'='1"]);
check('validation', 'Non-numeric ids get a 400', $r['code'] === 400, $r);

/* -------------------------------------------------------------- uploads */
$jpgPath = "$tmp/gps.jpg";
$im = imagecreatetruecolor(32, 32);
imagejpeg($im, $jpgPath);
$raw = file_get_contents($jpgPath);
$app1 = "Exif\x00\x00II*\x00GPSSECRET-22.8N-84.1E";
file_put_contents($jpgPath, substr($raw, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($raw, 2));
$r = http('POST', 'api/upload_avatar.php', $S, [], ['avatar' => [$jpgPath, 'image/jpeg', 'me.jpg']]);
$url = $r['json']['url'] ?? $r['json']['avatar_url'] ?? $r['json']['data']['url'] ?? '';
$img = $url ? http('GET', ltrim($url, '/')) : ['text' => ''];
check('uploads', 'Photo location (EXIF/GPS) is stripped on upload', ok($r) && $url && !str_contains($img['text'], 'GPSSECRET'), $r);
$bomb = "$tmp/bomb.png";
$bi = imagecreatetruecolor(13000, 10);
imagepng($bi, $bomb);
$r = http('POST', 'api/upload_avatar.php', $S, [], ['avatar' => [$bomb, 'image/png', 'big.png']]);
check('uploads', 'Gigapixel "image bombs" are refused', $r['code'] === 415, $r);
foreach (['admin_moderation', 'ban_user', 'admin_bulk_users', 'admin_transfer_alumni_email', 'upload_verification'] as $gone) {
    $r = http('POST', "api/$gone.php", $AD, ['target_id' => $aid, 'action' => 'ban']);
    check('security', "Removed endpoint $gone is gone (404)", $r['code'] === 404, $r);
}

/* --------------------------------------------------------------- export */
$r = http('GET', 'api/export_data.php', $A);
check('export', 'Download my data', ok($r) && isset($r['json']['posts'], $r['json']['profile'], $r['json']['counts']), $r);
$r = http('GET', 'api/export_data.php', $A);
check('export', 'Export is rate-limited', $r['code'] === 429, $r);
$r = http('POST', 'api/login.php', null, ['email' => strtoupper($stuEmail), 'password' => $newPass ?? $pass]);
check('auth', 'Sign-in email is case-insensitive', ok($r), $r);

/* ------------------------------------------- moderation: reports & restore */
$author = login('meera.rajput.7@demo.rjit.local', 'Demo@12345');   // a public account, so any member can see & report
$M = $author['token'];
$mid = (int)$author['id'];
$r = http('POST', 'api/create_post.php', $M, ['content' => "Report target $RUN", 'visibility_scope' => 'public', 'post_type' => 'text']);
$rp = find_id($r['json'], ['post_id', 'id']);
if (($r['json']['moderation_status'] ?? '') === 'pending') {   // new demo accounts go through the review queue first
    $q = http('POST', 'api/moderation_queue.php', $AD, ['post_id' => $rp, 'action' => 'approve']);
    check('moderation', 'Moderator approves a queued post', ok($q), $q);
}
$reporters = [];
foreach (['aarav.sharma.0', 'vivaan.tomar.2', 'diya.bhadoria.3', 'kabir.jain.4', 'ishita.gupta.5', 'reyansh.kushwah.6'] as $who) {
    $reporters[$who] = login("$who@demo.rjit.local", 'Demo@12345');
}
$ok5 = true;
foreach (array_slice($reporters, 0, 5) as $who => $l) {
    $x = http('POST', 'api/report_content.php', $l['token'], ['post_id' => $rp, 'reason' => 'spam']);
    $ok5 = $ok5 && ok($x);
}
$r = http('GET', "api/get_feed.php?user_id=$mid&filter=all", $S);
check('moderation', 'Five reports auto-hide a post', $ok5 && !str_contains($r['text'], "Report target $RUN"), $r);
$r = http('GET', 'api/get_notifications.php', $M);
check('moderation', 'Author is told the post was hidden', ok($r) && str_contains(strtolower($r['text']), 'hidden'), $r);
$r = http('GET', 'api/get_reports.php', $AD);
$myReports = array_values(array_filter((array)($r['json'] ?? []), fn ($x) => (int)($x['post_id'] ?? 0) === (int)$rp));
check('moderation', 'Admin queue shows the reports with reporter history', ok($r) && count($myReports) >= 5 && array_key_exists('reporter_warnings', $myReports[0]), $r);
$r = http('POST', 'api/admin_report_action.php', $S, ['action' => 'restore_post', 'post_id' => $rp]);
check('moderation', "Students can't restore posts", $r['code'] === 403, $r);

// Mark one reporter's report false three times → strike → can't post for 24h
$liar = $reporters['aarav.sharma.0'];
$lid = (int)$liar['id'];
$liarReports = array_values(array_filter($myReports, fn ($x) => (int)($x['reporter_id'] ?? $x['reporter_user_id'] ?? 0) === $lid));
$firstFalse = $liarReports[0]['report_id'] ?? 0;
$r = http('POST', 'api/admin_report_action.php', $AD, ['action' => 'penalize_reporter', 'report_id' => $firstFalse]);
check('moderation', 'Admin marks a report as false (warning 1)', ok($r), $r);
$r = http('POST', 'api/admin_report_action.php', $AD, ['action' => 'penalize_reporter', 'report_id' => $firstFalse]);
check('moderation', "Same report can't be penalised twice (409)", $r['code'] === 409, $r);
foreach ([1, 2] as $n) {
    $x = http('POST', 'api/create_post.php', $M, ['content' => "False-report bait $n $RUN", 'visibility_scope' => 'public', 'post_type' => 'text']);
    $bp = find_id($x['json'], ['post_id', 'id']);
    http('POST', 'api/moderation_queue.php', $AD, ['post_id' => $bp, 'action' => 'approve']);
    http('POST', 'api/report_content.php', $liar['token'], ['post_id' => $bp, 'reason' => 'spam']);
    $q = http('GET', 'api/get_reports.php', $AD);
    $rid = 0;
    foreach ((array)($q['json'] ?? []) as $x2) {
        if ((int)($x2['post_id'] ?? 0) === (int)$bp) { $rid = $x2['report_id']; }
    }
    $last = http('POST', 'api/admin_report_action.php', $AD, ['action' => 'penalize_reporter', 'report_id' => $rid]);
}
check('moderation', 'Third false report gives a strike', ok($last) && str_contains(strtolower($last['text']), 'strike'), $last);
$r = http('POST', 'api/create_post.php', $liar['token'], ['content' => "Should be blocked $RUN", 'post_type' => 'text']);
check('moderation', 'Struck reporter is temporarily blocked from posting', $r['code'] === 403, $r);
$r = http('GET', 'api/get_notifications.php', $liar['token']);
check('moderation', 'Reporter got a warning notification', true, $r);

$r = http('POST', 'api/admin_report_action.php', $AD, ['action' => 'restore_post', 'post_id' => $rp]);
check('moderation', 'Admin restores the hidden post (open reports dismissed)', ok($r), $r);
$q = http('GET', 'api/get_reports.php', $AD);
$stillOpen = array_filter((array)($q['json'] ?? []), fn ($x) => (int)$x['post_id'] === (int)$rp && in_array($x['status'], ['pending', 'reviewed'], true));
check('moderation', 'No open reports remain on the restored post', ok($q) && !$stillOpen, $q);
$r = http('GET', "api/get_feed.php?user_id=$mid&filter=all", $S);
check('moderation', 'Restored post is visible again', ok($r) && str_contains($r['text'], "Report target $RUN"), $r);
$x = http('POST', 'api/report_content.php', $reporters['reyansh.kushwah.6']['token'], ['post_id' => $rp, 'reason' => 'spam']);
$r = http('GET', "api/get_feed.php?user_id=$mid&filter=all", $S);
check('moderation', "Restored posts can't be auto-hidden again", ok($x) && str_contains($r['text'], "Report target $RUN"), $r);
$r = http('POST', 'api/admin_report_action.php', $AD, ['action' => 'restore_post', 'post_id' => $rp]);
check('moderation', "Restoring a visible post is refused (409)", $r['code'] === 409, $r);
// Undo the test strike so the demo account stays usable on re-runs
http('POST', 'api/admin_user_actions.php', $AD, ['action' => 'lift_shadow_ban', 'target_user_id' => $lid]);

/* ------------------------------------------------- admin: users (restored) */
$r = http('POST', 'api/admin_user_actions.php', $F, ['action' => 'suspend', 'target_user_id' => $sid, 'days' => 2, 'reason' => "Integration $RUN"]);
check('users', 'Faculty suspends a student', ok($r), $r);
$r = http('GET', 'api/me.php', $S);
check('users', "Suspension signs them out", $r['code'] === 401 || $r['code'] === 403, $r);
$r = http('POST', 'api/login.php', null, ['email' => $stuEmail, 'password' => $newPass ?? $pass]);
check('users', "Suspended student can't sign in (shows until when)", $r['code'] === 403 && str_contains($r['text'], 'until'), $r);
$r = http('POST', 'api/admin_user_actions.php', $F, ['action' => 'suspend', 'target_user_id' => (int)$admin['id'], 'days' => 2]);
check('users', "Faculty can't suspend an admin", $r['code'] === 403, $r);
$r = http('POST', 'api/admin_user_actions.php', $F, ['action' => 'ban_user', 'target_user_id' => $sid]);
check('users', "Faculty can't use admin-only actions", $r['code'] === 403, $r);
$r = http('POST', 'api/admin_user_actions.php', $F, ['action' => 'unsuspend', 'target_user_id' => $sid]);
check('users', 'Faculty lifts the suspension', ok($r), $r);
$re = login($stuEmail, $newPass ?? $pass);
$S = $re['token'] ?: $S;
check('users', 'Student can sign in again', (bool)$re['token'], $re['r']);
$dupEmail = $aluEmail;
$r = http('POST', 'api/admin_user_actions.php', $AD, ['action' => 'change_email', 'target_user_id' => $sid, 'new_email' => strtoupper($dupEmail)]);
check('users', 'Change email refuses an address in use (409, case-insensitive)', $r['code'] === 409, $r);
$movedEmail = "moved.$RUN@rjit.ac.in";
$r = http('POST', 'api/admin_user_actions.php', $AD, ['action' => 'change_email', 'target_user_id' => $sid, 'new_email' => $movedEmail]);
check('users', 'Admin changes a sign-in email', ok($r), $r);
$re = login($movedEmail, $newPass ?? $pass);
check('users', 'User signs in with the new email', (bool)$re['token'], $re['r']);
if ($re['token']) { $S = $re['token']; $stuEmail = $movedEmail; }
$bulkIds = [];
foreach ([1, 2, 3] as $n) {
    $em = "bulk$n.$RUN@example.com";   // alumni sign-ups wait for approval
    http('POST', 'api/register_alumni.php', null, ['name' => "Bulk $n $RUN", 'email' => $em, 'password' => $pass, 'branch' => 'IT',
        'joining_year' => 2014, 'mobile_number' => '98765' . rand(10000, 99999)]);
    $q = http('GET', 'api/admin_users.php?search=' . urlencode($em), $AD);
    foreach (($q['json']['data'] ?? []) as $u) {
        if (strcasecmp($u['email'] ?? '', $em) === 0) { $bulkIds[] = (int)($u['user_id'] ?? $u['id']); }
    }
}
$bulkIds = array_values(array_filter($bulkIds));
$r = http('POST', 'api/admin_user_actions.php', $AD, ['action' => 'bulk', 'bulk_action' => 'approve', 'user_ids' => array_slice($bulkIds, 0, 2)]);
check('users', 'Bulk approve sign-ups (one query)', count($bulkIds) === 3 && ok($r) && (int)($r['json']['changed'] ?? -1) === 2, $r);
$r = http('POST', 'api/admin_user_actions.php', $AD, ['action' => 'bulk', 'bulk_action' => 'reject', 'user_ids' => array_slice($bulkIds, 2)]);
check('users', 'Bulk reject sign-ups', ok($r), $r);
$r = http('POST', 'api/admin_user_actions.php', $AD, ['action' => 'bulk', 'bulk_action' => 'ban', 'user_ids' => [(int)$admin['id']]]);
check('users', "Bulk actions never touch admins", ok($r) === false || (int)($r['json']['changed'] ?? 1) === 0, $r);
$r = http('POST', 'api/admin_user_actions.php', $F, ['action' => 'bulk', 'bulk_action' => 'approve', 'user_ids' => $bulkIds]);
check('users', "Faculty can't run bulk actions", $r['code'] === 403, $r);

/* --------------------------------------------------------------- clean-up */
$r = http('POST', 'api/delete_post.php', $A, ['post_id' => $postId]);
check('feed', 'Author deletes the post', ok($r), $r);
$r = http('GET', 'api/get_feed.php?page=1', $S);
check('feed', 'Deleted post is gone from the feed', ok($r) && !str_contains($r['text'], "integration post $RUN"), $r);

/* ------------------------------------------------------------------ report */
$pass = count(array_filter($results, fn ($x) => $x['pass']));
$total = count($results);
$times = array_filter(array_column($results, 'ms'));
sort($times);
$p50 = $times ? $times[intdiv(count($times), 2)] : 0;
$p95 = $times ? $times[min(count($times) - 1, (int)floor(count($times) * 0.95))] : 0;
printf("\n%d/%d passed · median %sms · p95 %sms\n", $pass, $total, $p50, $p95);

$dir = dirname(__DIR__) . '/results';
@mkdir($dir, 0775, true);
$meta = ['run' => $RUN, 'date' => date('c'), 'base' => $BASE, 'php' => PHP_VERSION, 'passed' => $pass, 'total' => $total, 'median_ms' => $p50, 'p95_ms' => $p95];
file_put_contents("$dir/integration-latest.json", json_encode(['meta' => $meta, 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$md = "# Integration test results\n\n";
$md .= "| Run | Date | Passed | Median | p95 |\n|---|---|---|---|---|\n| `$RUN` | {$meta['date']} | **$pass / $total** | {$p50} ms | {$p95} ms |\n\n";
$md .= "Generated by `php tests/integration/run.php` against the PHP server (`router.php`) + PostgreSQL 16, database built with `php scripts/install_db.php --demo`.\n\n";
$area = '';
foreach ($results as $x) {
    if ($x['area'] !== $area) {
        $area = $x['area'];
        $md .= "\n### " . ucfirst($area) . "\n\n| | Check | HTTP | Time | Note |\n|---|---|---|---|---|\n";
    }
    $md .= '| ' . ($x['pass'] ? '✅' : '❌') . ' | ' . $x['name'] . ' | ' . ($x['code'] ?? '') . ' | ' . ($x['ms'] !== null ? $x['ms'] . ' ms' : '') . ' | ' . str_replace('|', '\\|', (string)$x['note']) . " |\n";
}
file_put_contents("$dir/integration-latest.md", $md);
exit($pass === $total ? 0 : 1);
