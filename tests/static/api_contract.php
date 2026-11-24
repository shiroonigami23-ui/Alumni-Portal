<?php
/**
 * API contract — runs in CI, needs no database.
 *
 * Every endpoint in api/ must declare who may call it with api_endpoint([...]) *before* it reads input,
 * touches the database or prints anything, using a known access level, and must never send raw
 * exception text to the client. Writes tests/results/api-manifest.json (the access map of the whole API).
 *
 *   php tests/static/api_contract.php
 */
require __DIR__ . '/../lib/api_manifest.php';
$root = dirname(__DIR__, 2);
$manifest = api_manifest($root);

$bad = 0;
$byLevel = [];
foreach ($manifest as $name => $e) {
    $label = $e['retired'] ? 'retired' : (is_array($e['access']) ? 'roles:' . implode('|', $e['access']) : (string)$e['access']);
    $byLevel[$label][] = $name;
    if ($e['problems']) {
        $bad++;
        echo "  ✗ $name\n";
        foreach ($e['problems'] as $p) {
            echo "      - $p\n";
        }
    }
}
ksort($byLevel);
foreach ($byLevel as $level => $names) {
    printf("  %-34s %3d  %s\n", $level, count($names), implode(' ', array_map(fn ($n) => substr($n, 0, -4), array_slice($names, 0, 8))) . (count($names) > 8 ? ' …' : ''));
}
$out = getenv('MANIFEST_OUT') ?: $root . '/tests/results/api-manifest.json';
@mkdir(dirname($out), 0775, true);
file_put_contents($out, json_encode(['generated' => date('c'), 'endpoints' => count($manifest), 'violations' => $bad,
    'by_access' => array_map('count', $byLevel), 'endpoints_detail' => $manifest], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo count($manifest) . " endpoints, $bad with contract violations\n";
exit($bad ? 1 : 0);
