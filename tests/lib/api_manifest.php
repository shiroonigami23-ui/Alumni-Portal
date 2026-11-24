<?php
/**
 * Reads every endpoint's api_endpoint([...]) declaration straight from the source.
 * Shared by tests/static/api_contract.php (is every endpoint declared?) and
 * tests/integration/authz.php (attack every non-public endpoint with the wrong role).
 */
function api_manifest(string $root): array
{
    $out = [];
    foreach (glob($root . '/api/*.php') as $file) {
        $name = basename($file);
        if ($name[0] === '_') {
            continue;   // helpers; the router refuses to serve these
        }
        $src = (string)file_get_contents($file);
        $entry = ['file' => "api/$name", 'declared' => false, 'access' => null, 'actions' => [], 'retired' => str_contains($src, 'api_retired('),
                  'problems' => []];
        $pos = strpos($src, 'api_endpoint([');
        if ($pos === false) {
            $entry['problems'][] = 'no api_endpoint() declaration';
            $out[$name] = $entry;
            continue;
        }
        $entry['declared'] = true;
        $decl = substr($src, $pos, 600);
        if (preg_match("/'access'\s*=>\s*'(\w+)'/", $decl, $m)) {
            $entry['access'] = $m[1];
        } elseif (preg_match("/'access'\s*=>\s*\[([^\]]*)\]/", $decl, $m)) {
            preg_match_all("/'(\w+)'/", $m[1], $mm);
            $entry['access'] = $mm[1];
        } else {
            $entry['problems'][] = "access level isn't a literal";
        }
        if (preg_match("/'actions'\s*=>\s*\[(.*?)\]\s*[,\]]/s", $decl, $m)) {
            preg_match_all("/'([\w-]+)'\s*=>\s*'(\w+)'/", $m[1], $mm, PREG_SET_ORDER);
            foreach ($mm as $pair) {
                $entry['actions'][$pair[1]] = $pair[2];
            }
        }
        // Nothing may read input, touch the database or print before access is enforced.
        $before = '';
        foreach (token_get_all(substr($src, 0, $pos)) as $t) {   // code only — comments may mention $_FILES etc.
            $before .= is_array($t) ? (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $t[1]) : $t;
        }
        foreach (['$_GET', '$_POST', '$_REQUEST', '$_FILES', 'php://input', '->prepare(', '->query(', '->exec(', 'echo ', 'new Database'] as $risky) {
            if (str_contains($before, $risky)) {
                $entry['problems'][] = "uses $risky before api_endpoint()";
            }
        }
        // Raw exception text must never reach the browser.
        foreach (preg_split('/\R/', $src) as $i => $line) {
            if (str_contains($line, 'getMessage()') && !preg_match('/error_log\(|getCode\(\)/', $line)) {
                $entry['problems'][] = 'line ' . ($i + 1) . ': exception message sent to the client (use api_error_message())';
            }
        }
        $valid = ['public', 'member', 'staff', 'admin', 'moderator'];
        $roles = ['student', 'alumni', 'faculty', 'admin'];
        $acc = $entry['access'];
        if ($acc !== null && !(is_array($acc) ? ($acc && !array_diff($acc, $roles)) : in_array($acc, $valid, true))) {
            $entry['problems'][] = 'unknown access level ' . json_encode($acc);
        }
        foreach ($entry['actions'] as $a => $lvl) {
            if (!in_array($lvl, $valid, true)) {
                $entry['problems'][] = "unknown access level '$lvl' for action $a";
            }
        }
        $out[$name] = $entry;
    }
    ksort($out);
    return $out;
}
