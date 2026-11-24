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
$r = http('POST', 'api/share_post.php', $S, ['post_id' => $postId]);
check('feed', 'Share', ok($r), $r);
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
$r = http('POST', 'api/create_event.php', $AD, ['title' => "Alumni Meet $RUN", 'description' => 'Annual meet', 'event_date' => $d, 'event_time' => '18:00',
    'end_date' => $d, 'end_time' => '21:00', 'location' => 'RJIT Auditorium', 'visibility' => 'public', 'rsvp_limit' => 100]);
$eventId = find_id($r['json'], ['event_id', 'id']);
check('events', 'Create an event', ok($r) && $eventId, $r);
$r = http('GET', 'api/get_events.php', $S);
check('events', 'Events list shows it', ok($r) && str_contains($r['text'], "Alumni Meet $RUN"), $r);
$r = http('POST', 'api/rsvp_event.php', $S, ['event_id' => $eventId, 'status' => 'going']);
check('events', 'RSVP', ok($r), $r);
$r = http('GET', "api/get_event_attendees.php?event_id=$eventId", $AD);
check('events', 'Attendee list', ok($r), $r);
$r = http('POST', 'api/cancel_rsvp.php', $S, ['event_id' => $eventId]);
check('events', 'Cancel RSVP', ok($r), $r);
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
$r = http('POST', 'api/upload_verification.php', $A, [], ['document' => ["$tmp/fake.pdf", 'application/pdf', 'proof.pdf']]);
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
