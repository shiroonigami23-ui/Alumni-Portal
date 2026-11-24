<?php
/**
 * Authorization sweep: ordinary members try every staff-only endpoint.
 *
 * The target list is built from the endpoints' own api_endpoint() declarations (tests/lib/api_manifest.php),
 * so a new admin/staff/moderator endpoint — or a stricter per-action level — is attacked automatically.
 * A few member endpoints with owner-only checks are listed by hand at the bottom of $endpoints.
 *
 * A student and an alumnus send a catch-all payload (ban/approve/delete/transfer, targeting another member)
 * to each admin/faculty endpoint. Passing means: the request is refused (4xx or success=false) AND a
 * fingerprint of users/posts/events/strikes/tokens/banner is byte-for-byte unchanged.
 *
 *   BASE_URL=http://127.0.0.1:8099/ DB_NAME=alumni_portal php tests/integration/authz.php
 */
$BASE = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8099/', '/') . '/';
$results = [];

function req(string $method, string $path, ?string $token, $body = null): array
{
    global $BASE;
    $ch = curl_init($BASE . $path);
    $h = ['Accept: application/json'];
    if ($token) {
        $h[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 20]);
    if ($body !== null) {
        $h[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
    $raw = (string)curl_exec($ch);
    return ['code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE), 'json' => json_decode($raw, true), 'raw' => $raw];
}
function token(string $email, string $pw): string
{
    $r = req('POST', 'api/login.php', null, ['email' => $email, 'password' => $pw]);
    $t = $r['json']['token'] ?? ($r['json']['data']['token'] ?? '');
    if (!$t) {
        fwrite(STDERR, "login failed for $email: {$r['raw']}\n");
        exit(2);
    }
    return $t;
}

$pdo = new PDO(sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '5432', getenv('DB_NAME') ?: 'alumni_portal'),
    getenv('DB_USER') ?: 'postgres', getenv('DB_PASSWORD') ?: '');
$fingerprint = function () use ($pdo): string {
    $parts = [];
    foreach ([
        "SELECT user_id, role, status, email, COALESCE(is_moderator, FALSE) FROM users ORDER BY user_id",
        "SELECT post_id, status, COALESCE(moderation_status, ''), post_type FROM posts ORDER BY post_id",
        "SELECT event_id, status::text FROM events ORDER BY event_id",
        "SELECT * FROM moderation_strikes ORDER BY 1",
        "SELECT COUNT(*) FROM alumni_tokens",
        "SELECT key, value FROM portal_settings ORDER BY key",
        "SELECT report_id, status::text FROM reports ORDER BY report_id",
        "SELECT COUNT(*) FROM announcements",
    ] as $sql) {
        try {
            $parts[] = json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_NUM));
        } catch (Throwable $e) {
            $parts[] = 'n/a';
        }
    }
    return hash('sha256', implode('|', $parts));
};

$victim = (int)$pdo->query("SELECT user_id FROM users WHERE role = 'alumni' AND email LIKE '%demo.rjit.local' ORDER BY user_id DESC LIMIT 1")->fetchColumn();
$admin = (int)$pdo->query("SELECT user_id FROM users WHERE role = 'admin' ORDER BY user_id LIMIT 1")->fetchColumn();
$post = (int)$pdo->query("SELECT post_id FROM posts ORDER BY post_id LIMIT 1")->fetchColumn();
$event = (int)($pdo->query("SELECT event_id FROM events ORDER BY event_id LIMIT 1")->fetchColumn() ?: 1);
$report = (int)($pdo->query("SELECT report_id FROM reports ORDER BY report_id LIMIT 1")->fetchColumn() ?: 1);

$payload = [
    'target_id' => $victim, 'target_user_id' => $victim, 'user_id' => $victim, 'reporter_id' => $victim, 'mentor_user_id' => $victim,
    'user_ids' => [$victim, $admin], 'post_id' => $post, 'event_id' => $event, 'report_id' => $report, 'announcement_id' => $post,
    'action' => 'ban', 'status' => 'banned', 'days' => 7, 'duration' => 7, 'reason' => 'authz test', 'new_email' => 'stolen@evil.test',
    'email' => 'stolen@evil.test', 'role' => 'admin', 'count' => 5, 'message' => 'pwned', 'tone' => 'danger', 'title' => 'pwned',
    'content' => 'pwned', 'is_moderator' => true, 'decision' => 'approve',
];
require __DIR__ . '/../lib/api_manifest.php';
$manifest = api_manifest(dirname(__DIR__, 2));
$auto = [];   // [method, path, extra payload, roles allowed]
foreach ($manifest as $name => $e) {
    if ($e['retired'] || !$e['declared']) {
        continue;
    }
    $levels = [];
    if ($e['access'] !== 'public' && $e['access'] !== 'member') {
        $levels[''] = $e['access'];
    }
    foreach ($e['actions'] as $action => $lvl) {
        $levels[$action] = $lvl;
    }
    foreach ($levels as $action => $lvl) {
        $q = $action !== '' ? '?action=' . rawurlencode($action) : '';
        $extra = $action !== '' ? ['action' => $action] : [];
        $auto[] = ['POST', "api/$name$q", $extra, $lvl];
        $auto[] = ['GET', "api/$name$q", null, $lvl];
    }
}
$endpoints = [
    ['POST', 'api/admin_moderation.php'], ['POST', 'api/ban_user.php'], ['POST', 'api/kick_user.php'],
    ['POST', 'api/approve_user.php'], ['POST', 'api/approve_user.php', ['action' => 'reject']], ['POST', 'api/process_fake_report.php'],
    ['POST', 'api/restore_content.php'], ['POST', 'api/approve_announcement.php'], ['POST', 'api/approve_event.php'],
    ['POST', 'api/admin_bulk_users.php'], ['POST', 'api/admin_transfer_alumni_email.php'],
    ['POST', 'api/admin_user_actions.php'], ['POST', 'api/admin_user_actions.php', ['action' => 'set_role']],
    ['POST', 'api/admin_user_actions.php', ['action' => 'delete']], ['POST', 'api/admin_report_action.php'],
    ['POST', 'api/admin_report_action.php', ['action' => 'dismiss']], ['POST', 'api/generate_token.php'],
    ['POST', 'api/moderate_post.php', null, 'staff'], ['POST', 'api/moderation_queue.php', ['action' => 'approve']],
    ['POST', 'api/moderation_queue.php', ['action' => 'reject']],
    ['POST', 'api/admin_insights.php', ['action' => 'broadcast']], ['POST', 'api/create_announcement.php'],
    ['POST', 'api/mentorship.php?action=review_application', ['status' => 'approved']],
    ['POST', 'api/success_stories.php?action=approve', ['story_id' => 1]], ['POST', 'api/events.php?action=approve'],
    ['GET', 'api/admin_users.php'], ['GET', 'api/admin_stats.php'], ['GET', 'api/view_logs.php'], ['GET', 'api/get_reports.php'],
    ['GET', 'api/admin_export_users.php'], ['GET', 'api/admin_insights.php'], ['GET', "api/get_applicants.php?job_id=1", null, 'admin'],
];

$attackers = [
    'student' => token('ayaan.tomar@demo.rjit.local', 'Demo@12345'),
    'alumni' => token('ananya.verma.1@demo.rjit.local', 'Demo@12345'),
    'faculty' => token('neha.kulkarni.faculty@demo.rjit.local', 'Demo@12345'),
];
/** Would this attacker legitimately be allowed in? (then it isn't an attack) */
function allowed_for(string $who, $level): bool
{
    if (is_array($level)) {
        return in_array($who, $level, true);
    }
    return $level === 'member' || $level === 'public' || ($level === 'staff' && $who === 'faculty');
}
// hand-listed targets count as admin-only unless stated
$targets = [];
foreach ($endpoints as $ep) {
    // the endpoint's own declaration decides who is legitimately allowed
    $file = basename(parse_url($ep[1], PHP_URL_PATH));
    parse_str((string)parse_url($ep[1], PHP_URL_QUERY), $qs);
    $act = strtolower((string)($qs['action'] ?? ($ep[2]['action'] ?? '')));
    $decl = $manifest[$file] ?? null;
    $level = $ep[3] ?? ($decl ? ($decl['actions'][$act] ?? $decl['access']) : 'admin');
    if ($level === 'member' && !isset($ep[3])) {
        $level = 'admin';   // member-level endpoints in the hand list guard admin-only actions inside
    }
    $targets[] = [$ep[0], $ep[1], $ep[2] ?? null, $level];
}
foreach ($auto as $ep) {
    $targets[] = $ep;
}
$seen = [];
$targets = array_values(array_filter($targets, function ($t) use (&$seen) {
    $k = $t[0] . ' ' . $t[1] . ' ' . json_encode($t[2]);
    return isset($seen[$k]) ? false : ($seen[$k] = true);
}));
$sensitive = ['admin_users', 'view_logs', 'get_reports', 'admin_export_users', 'admin_stats', 'admin_insights'];

$pass = 0;
$total = 0;
foreach ($attackers as $who => $tok) {
    foreach ($targets as $ep) {
        [$method, $path, $epExtra, $level] = $ep;
        if (allowed_for($who, $level)) {
            continue;
        }
        $body = $method === 'POST' ? array_merge($payload, $epExtra ?? []) : null;
        $before = $fingerprint();
        $r = req($method, $path, $tok, $body);
        $after = $fingerprint();
        $refused = $r['code'] >= 400 || (is_array($r['json']) && (($r['json']['success'] ?? null) === false || ($r['json']['status'] ?? '') === 'error'));
        $leak = false;
        foreach ($sensitive as $s) {
            if (str_contains($path, $s) && $r['code'] < 400 && strlen($r['raw']) > 200) {
                $leak = true;
            }
        }
        if ($path === 'api/get_applicants.php?job_id=1' && $r['code'] < 400 && preg_match('/@/', $r['raw'])) {
            $leak = true;
        }
        $ok = $refused && $before === $after && !$leak;
        $total++;
        $pass += $ok ? 1 : 0;
        $label = $method . ' ' . $path . ($epExtra ? ' ' . json_encode($epExtra) : '');
        $results[] = ['as' => $who, 'endpoint' => $label, 'http' => $r['code'], 'refused' => $refused, 'db_unchanged' => $before === $after, 'pass' => $ok];
        printf("  %s %-8s %-70s %d%s\n", $ok ? '✓' : '✗', $who, mb_substr($label, 0, 70), $r['code'], $ok ? '' : ($before !== $after ? '  ← DATABASE CHANGED' : '  ← not refused: ' . mb_substr($r['raw'], 0, 90)));
        if ($before !== $after) {
            // Undo anything a broken endpoint did so the rest of the sweep stays meaningful.
            $pdo->exec("UPDATE users SET status = 'active' WHERE email LIKE '%demo.rjit.local' OR role = 'admin'");
        }
    }
}

$out = getenv('AUTHZ_OUT') ?: 'tests/results/authz-latest.json';
@mkdir(dirname($out), 0775, true);
file_put_contents($out, json_encode(['generated' => date('c'), 'passed' => $pass, 'total' => $total, 'results' => $results], JSON_PRETTY_PRINT));
echo "$pass/$total privileged requests refused with no data changed\n";
exit($pass === $total ? 0 : 1);
