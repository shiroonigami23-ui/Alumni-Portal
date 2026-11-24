<?php
/**
 * Latency benchmark: two running copies of the app (e.g. the previous commit and this one), same database,
 * interleaved requests so machine noise cancels out. Reports the median per endpoint and overall p95.
 *
 *   BEFORE_URL=http://127.0.0.1:8095/ AFTER_URL=http://127.0.0.1:8099/ php tests/perf/bench.php 200
 *
 * For production-like numbers, run both apps through tests/perf/latency_proxy.py (database latency
 * dominates real requests, and is invisible when PostgreSQL is on the same machine).
 */
$servers = ['before' => getenv('BEFORE_URL') ?: 'http://127.0.0.1:8095/', 'after' => getenv('AFTER_URL') ?: 'http://127.0.0.1:8099/'];
$N = (int)($argv[1] ?? 200);
function req($url, $tok, $body = null) {
    $ch = curl_init($url);
    $h = ['Authorization: Bearer ' . $tok];
    if ($body !== null) { $h[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h]);
    $t = hrtime(true); $raw = curl_exec($ch); $ms = (hrtime(true) - $t) / 1e6;
    return [$ms, curl_getinfo($ch, CURLINFO_HTTP_CODE)];
}
$tok = [];
foreach ($servers as $k => $base) {
    $ch = curl_init($base . 'api/login.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POSTFIELDS => json_encode(['email' => (getenv('BENCH_EMAIL') ?: 'aarav.sharma.0@demo.rjit.local'), 'password' => (getenv('BENCH_PASSWORD') ?: 'Demo@12345')]), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
    $tok[$k] = json_decode(curl_exec($ch), true)['token'];
}
$eps = ['me.php', 'get_feed.php?sort=for_you', 'get_notifications.php', 'get_unread_messages_count.php', 'get_conversations.php',
        'search_directory.php?search=sh', 'get_user_profile.php?user_id=5', 'get_jobs.php', 'master_search.php?q=ana'];
printf("%-34s %12s %12s %10s\n", 'endpoint (median ms, n=' . $N . ')', 'before', 'after', 'change');
$tot = ['before' => [], 'after' => []];
foreach ($eps as $ep) {
    $t = ['before' => [], 'after' => []];
    for ($i = 0; $i < 15; $i++) { foreach ($servers as $k => $b) req($b . 'api/' . $ep, $tok[$k]); }   // warm-up
    for ($i = 0; $i < $N; $i++) {
        foreach (($i % 2 ? ['after', 'before'] : ['before', 'after']) as $k) {   // interleave to cancel drift
            [$ms, $code] = req($servers[$k] . 'api/' . $ep, $tok[$k]);
            if ($code >= 400) { fwrite(STDERR, "$k $ep -> $code\n"); }
            $t[$k][] = $ms;
        }
    }
    $med = []; foreach ($t as $k => $v) { sort($v); $med[$k] = $v[intdiv(count($v), 2)]; $tot[$k] = array_merge($tot[$k], $v); }
    printf("%-34s %12.2f %12.2f %9.1f%%\n", $ep, $med['before'], $med['after'], 100 * ($med['after'] - $med['before']) / $med['before']);
}
foreach ($tot as $k => $v) { sort($v); $p[$k] = [$v[intdiv(count($v), 2)], $v[(int)(count($v) * .95)]]; }
printf("%-34s %12.2f %12.2f %9.1f%%\n", 'ALL median', $p['before'][0], $p['after'][0], 100 * ($p['after'][0] - $p['before'][0]) / $p['before'][0]);
printf("%-34s %12.2f %12.2f %9.1f%%\n", 'ALL p95', $p['before'][1], $p['after'][1], 100 * ($p['after'][1] - $p['before'][1]) / $p['before'][1]);
