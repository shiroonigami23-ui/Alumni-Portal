<?php
/**
 * SQL-injection fuzz: every API endpoint × every common parameter × classic payloads, as admin and as student.
 *
 * Fails on: HTTP 500, a database error leaking into the response, a SQL syntax error in the server log,
 * or a response slowed by an injected pg_sleep (time-based blind injection).
 *
 *   BASE_URL=http://127.0.0.1:8099/ SERVER_LOG=/tmp/srv.log php tests/integration/sqli_fuzz.php
 */
$BASE = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8099/', '/') . '/';
$LOG = getenv('SERVER_LOG') ?: '';
$root = dirname(__DIR__, 2);

function call(string $method, string $url, ?string $token, $json = null): array
{
    $ch = curl_init($url);
    $h = ['Accept: application/json'];
    if ($token) {
        $h[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 15]);
    if ($json !== null) {
        $h[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
    $t = microtime(true);
    $raw = (string)curl_exec($ch);
    return ['code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE), 'raw' => $raw, 'ms' => (microtime(true) - $t) * 1000];
}
function login(string $base, string $email, string $pw): string
{
    $r = call('POST', $base . 'api/login.php', null, ['email' => $email, 'password' => $pw]);
    $j = json_decode($r['raw'], true);
    return (string)($j['token'] ?? ($j['data']['token'] ?? ''));
}

$payloads = [
    "1' OR '1'='1",
    "1); SELECT pg_sleep(3);--",
    "1' AND (SELECT 1 FROM pg_sleep(3))::text='1",
    "' UNION SELECT NULL,NULL,NULL--",
    "1 OR 1=1",
    "\\'; DROP TABLE users;--",
    "%' OR full_name ILIKE '%",
];
$params = ['id', 'user_id', 'post_id', 'job_id', 'event_id', 'poll_id', 'option_id', 'offer_id', 'story_id', 'resource_id', 'comment_id',
           'conversation_id', 'group_id', 'q', 'search', 'sort', 'filter', 'role', 'status', 'branch', 'company', 'location', 'tech',
           'city', 'page', 'limit', 'category', 'type', 'since', 'year', 'graduation_year', 'mentor_id', 'request_id', 'token', 'email'];
$skip = ['login.php', 'register.php', 'register_alumni.php', 'register_student.php', 'register_faculty.php', 'request_reset.php',
         'reset_password.php', 'csrf_token.php', 'export_data.php', 'admin_export_users.php', 'asset.php', 'search_gifs.php',
         'toggle_stream.php', 'admin_user_actions.php', 'ban_user.php', 'kick_user.php', 'admin_bulk_users.php', 'block_user.php',
         'admin_transfer_alumni_email.php', 'change_password.php', 'delete_post.php', 'connect_user.php', 'admin_insights.php'];

$tokens = ['admin' => login($BASE, 'admin@rjit.ac.in', getenv('ADMIN_PASSWORD') ?: 'Admin@12345'),
           'student' => login($BASE, 'ayaan.tomar@demo.rjit.local', 'Demo@12345')];
$logStart = $LOG && is_file($LOG) ? filesize($LOG) : 0;

$endpoints = array_values(array_filter(array_map('basename', glob($root . '/api/*.php')), fn ($f) => $f[0] !== '_' && !in_array($f, $skip, true)));
$fails = [];
$n = 0;
foreach ($tokens as $who => $tok) {
    foreach ($endpoints as $ep) {
        foreach ($payloads as $pi => $p) {
            $q = http_build_query(array_fill_keys($params, $p) + ['action' => 'list']);
            foreach ([['GET', $BASE . "api/$ep?$q", null], ['POST', $BASE . "api/$ep?action=list", array_fill_keys($params, $p)]] as [$m, $url, $body]) {
                $r = call($m, $url, $tok, $body);
                $n++;
                $why = [];
                if ($r['code'] >= 500) {
                    $why[] = "HTTP {$r['code']}";
                }
                if (preg_match('/SQLSTATE|syntax error at or near|unterminated quoted|pg_query|PDOException/i', $r['raw'])) {
                    $why[] = 'database error in response';
                }
                if ($r['ms'] > 2800 && str_contains($p, 'pg_sleep')) {
                    $why[] = sprintf('slow (%.0f ms) — possible time-based injection', $r['ms']);
                }
                if ($why) {
                    $fails[] = sprintf('%-7s %-4s %-28s payload#%d  %s  %s', $who, $m, $ep, $pi, implode(', ', $why), mb_substr(preg_replace('/\s+/', ' ', $r['raw']), 0, 120));
                }
            }
        }
    }
}
$logErrors = [];
if ($LOG && is_file($LOG)) {
    $fh = fopen($LOG, 'r');
    fseek($fh, $logStart);
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/syntax error at or near|unterminated quoted string|SQLSTATE\[42601\]/i', $line)) {
            $logErrors[] = trim(mb_substr($line, 0, 200));
        }
    }
}
foreach ($fails as $f) {
    echo "  ✗ $f\n";
}
foreach (array_slice(array_unique($logErrors), 0, 20) as $l) {
    echo "  ✗ log: $l\n";
}
$ok = !$fails && !$logErrors;
$out = getenv('SQLI_OUT') ?: 'tests/results/sqli-latest.json';
@mkdir(dirname($out), 0775, true);
file_put_contents($out, json_encode(['generated' => date('c'), 'requests' => $n, 'endpoints' => count($endpoints), 'payloads' => count($payloads),
    'failures' => $fails, 'sql_syntax_errors_in_log' => array_values(array_unique($logErrors)), 'pass' => $ok], JSON_PRETTY_PRINT));
printf("%d injection requests across %d endpoints: %s\n", $n, count($endpoints), $ok ? 'no injection, no 500s, no SQL errors' : count($fails) . ' failures, ' . count($logErrors) . ' SQL syntax errors in log');
exit($ok ? 0 : 1);
