<?php
/**
 * Front controller / router.
 *
 * Every request for a .php file — and every URL that doesn't match a real
 * static file — lands here (Apache via .htaccess, or `php -S ... router.php`).
 *
 *   /feed.php, /feed           -> pages/feed.php        (old URL keeps working)
 *   /login.php, /signin        -> pages/auth/login.php  (aliases 301 to canonical)
 *   /api/get_feed.php          -> api/get_feed.php      (after the security layer)
 *   /edit-profile.php          -> 301 settings.php#profile
 *   anything unknown           -> friendly 404 with suggestions
 *
 * URLs in the browser never change, so every relative link, asset path and
 * API call in the existing pages keeps resolving exactly as before.
 */

define('APP_ROOT', __DIR__);

$__r = [];
$__r['routes'] = require APP_ROOT . '/bootstrap/routes.php';

// ---- Work out the request path relative to where the app is installed -----
$__r['uri'] = (string)($_SERVER['REQUEST_URI'] ?? '/');
$__r['path'] = rawurldecode((string)parse_url($__r['uri'], PHP_URL_PATH));
$__r['query'] = (string)parse_url($__r['uri'], PHP_URL_QUERY);
$__r['self'] = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/router.php'));
$__r['base'] = rtrim(str_replace('\\', '/', dirname($__r['self'])), '/');   // '' or '/alumni-portal'
if (PHP_SAPI === 'cli-server') {
    $__r['base'] = '';
}
$__r['rel'] = $__r['path'];
if ($__r['base'] !== '' && strpos($__r['rel'], $__r['base'] . '/') === 0) {
    $__r['rel'] = substr($__r['rel'], strlen($__r['base']));
} elseif ($__r['base'] !== '' && $__r['rel'] === $__r['base']) {
    $__r['rel'] = '/';
}
$__r['rel'] = ltrim($__r['rel'], '/');

/** Build an absolute URL path inside the app. */
function ap_url(string $relative): string
{
    global $__r;
    return $__r['base'] . '/' . ltrim($relative, '/');
}

function ap_redirect(string $target, int $code = 301): void
{
    global $__r;
    $hash = '';
    if (($p = strpos($target, '#')) !== false) {
        $hash = substr($target, $p);
        $target = substr($target, 0, $p);
    }
    $q = $__r['query'] !== '' ? (strpos($target, '?') === false ? '?' : '&') . $__r['query'] : '';
    header('Location: ' . ap_url($target) . $q . $hash, true, $code);
    exit;
}

// ---- Reject path tricks early ----------------------------------------------
if ($__r['rel'] !== '' && (strpos($__r['rel'], "\0") !== false || preg_match('#(^|/)\.\.?(/|$)#', $__r['rel']))) {
    http_response_code(400);
    exit('Bad request');
}

// ---- Health check (used by Docker/ECS) — no DB, no session ------------------
if ($__r['rel'] === 'healthz') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(['status' => 'ok', 'time' => gmdate('c')]);
    exit;
}

// ---- Static files under the built-in PHP server ----------------------------
if (PHP_SAPI === 'cli-server') {
    $__r['file'] = APP_ROOT . '/' . $__r['rel'];
    if ($__r['rel'] !== '' && is_file($__r['file']) && !preg_match('/\.php$/i', $__r['rel'])) {
        require_once APP_ROOT . '/bootstrap/static_policy.php';
        if (!ap_static_allowed($__r['rel'])) {
            http_response_code(404);
            exit('Not found');
        }
        return false; // let the built-in server stream it
    }
}

// ---- Security layer for everything that executes PHP -----------------------
require_once APP_ROOT . '/bootstrap/security.php';

// ---- Normalise: strip trailing slash and ".php" to get a route key ---------
$__r['key'] = rtrim($__r['rel'], '/');
if ($__r['key'] !== $__r['rel'] && $__r['key'] !== '') {
    ap_redirect($__r['key']); // /feed/ -> /feed  (keeps relative links correct)
}
$__r['slug'] = preg_replace('/\.php$/i', '', $__r['key']);

// ---- 1) Pages ---------------------------------------------------------------
$__r['target'] = null;
if (array_key_exists($__r['slug'], $__r['routes']['pages'])) {
    $__r['target'] = $__r['routes']['pages'][$__r['slug']];
    $__r['virtual'] = $__r['key'] === '' ? 'index.php' : $__r['slug'] . '.php';
}

// ---- 2) Redirects for old / alternate URLs ---------------------------------
if ($__r['target'] === null && isset($__r['routes']['redirects'][$__r['slug']])) {
    ap_redirect($__r['routes']['redirects'][$__r['slug']]);
}

// ---- 2b) Someone typed the internal file path (/pages/feed.php) -> canonical URL
if ($__r['target'] === null && preg_match('#^pages/(?:auth/|legal/)?([a-z0-9-]+)(\.php)?$#', $__r['key'], $__m)
    && isset($__r['routes']['pages'][$__m[1]])) {
    ap_redirect($__m[1] . '.php');
}

// ---- 3) Directly executable scripts (api/*.php, admin/*.php) ---------------
if ($__r['target'] === null && preg_match('#^([a-z0-9_-]+)/([A-Za-z0-9_.-]+)$#', $__r['key'], $__m)) {
    $__r['dir'] = $__m[1];
    $__r['name'] = $__m[2];
    if (in_array($__r['dir'], $__r['routes']['script_dirs'], true) && $__r['name'][0] !== '_') {
        if (!preg_match('/\.php$/i', $__r['name'])) {
            $__r['name'] .= '.php'; // allow /api/get_feed as well as /api/get_feed.php
        }
        if (is_file(APP_ROOT . '/' . $__r['dir'] . '/' . $__r['name'])) {
            $__r['target'] = $__r['dir'] . '/' . $__r['name'];
            $__r['virtual'] = $__r['target'];
        }
    }
    unset($__m);
}

// ---- 4) Nothing matched: friendly 404 --------------------------------------
if ($__r['target'] === null) {
    ap_reject(404, "We couldn't find that page. It may have moved — try one of the links below.");
}

// ---- Run the target as if it had been requested directly -------------------
$__r['file'] = APP_ROOT . '/' . $__r['target'];
$__r['vpath'] = $__r['base'] . '/' . $__r['virtual'];
$_SERVER['SCRIPT_NAME'] = $__r['vpath'];
$_SERVER['PHP_SELF'] = $__r['vpath'];
$_SERVER['SCRIPT_FILENAME'] = $__r['file'];
// Pages lived at the project root, so they run from there; api/admin run from their own folder.
// Either way relative includes ('includes/header.php', '../config/Database.php') resolve as before.
$__r['cwd'] = isset($__r['routes']['pages'][$__r['slug']]) ? APP_ROOT : dirname($__r['file']);
chdir($__r['cwd']);
set_include_path($__r['cwd'] . PATH_SEPARATOR . APP_ROOT . PATH_SEPARATOR . get_include_path());

$__target_file = $__r['file'];
unset($__r['routes']);
require $__target_file;
