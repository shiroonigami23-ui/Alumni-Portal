<?php
/**
 * Security bootstrap — runs before every page and API script (via router.php).
 *
 *  - Hardened session cookies (HttpOnly, SameSite, Secure on HTTPS, strict mode)
 *  - Security headers + Content-Security-Policy
 *  - CORS locked to same-origin (+ ALLOWED_ORIGINS env), overriding legacy "*"
 *  - Trusted-proxy aware client IP (X-Forwarded-For can't be spoofed)
 *  - Rate limiting (global API budget + strict auth budget)
 *  - Login brute-force lockout per account + IP
 *  - Upload inspection (real MIME sniffing, double-extension & embedded-code checks)
 *  - Method allowlist and request-size cap
 *
 * Every limit is tunable with environment variables (see .env.example) and every
 * rejection returns a clear, friendly message.
 */

if (defined('AP_SECURITY_LOADED')) {
    return;
}
define('AP_SECURITY_LOADED', true);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/* ----------------------------------------------------------------- helpers */

function ap_env(string $key, $default = null)
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($v === null || $v === '') ? $default : $v;
}

function ap_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    // Only believe the proxy header when it came from a trusted proxy (e.g. AWS ALB)
    return ap_from_trusted_proxy()
        && strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function ap_ip_in_cidr(string $ip, string $cidr): bool
{
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    [$subnet, $bits] = explode('/', $cidr, 2);
    $ipBin = @inet_pton($ip);
    $subBin = @inet_pton($subnet);
    if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
        return false;
    }
    $bits = (int)$bits;
    $bytes = intdiv($bits, 8);
    $rem = $bits % 8;
    if ($bytes && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
        return false;
    }
    if ($rem) {
        $mask = chr((0xff << (8 - $rem)) & 0xff);
        return (($ipBin[$bytes] & $mask) === ($subBin[$bytes] & $mask));
    }
    return true;
}

/** Is the direct peer a proxy we trust to set X-Forwarded-* headers? */
function ap_from_trusted_proxy(): bool
{
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($remote === '') {
        return false;
    }
    // Default: loopback + private networks (load balancer inside the VPC / docker network)
    $list = (string)ap_env('TRUSTED_PROXIES', '127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16');
    foreach (array_filter(array_map('trim', explode(',', $list))) as $cidr) {
        if (ap_ip_in_cidr($remote, $cidr)) {
            return true;
        }
    }
    return false;
}

/** The real client IP. X-Forwarded-For is only honoured from trusted proxies. */
function ap_client_ip(): string
{
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    if (!ap_from_trusted_proxy() || empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $remote;
    }
    // Walk right-to-left, skipping our own proxies; first untrusted hop is the client.
    $hops = array_reverse(array_map('trim', explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR'])));
    foreach ($hops as $hop) {
        if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
            continue;
        }
        $saved = $_SERVER['REMOTE_ADDR'];
        $_SERVER['REMOTE_ADDR'] = $hop;
        $trusted = ap_from_trusted_proxy();
        $_SERVER['REMOTE_ADDR'] = $saved;
        if (!$trusted) {
            return $hop;
        }
    }
    return $remote;
}

function ap_is_api_request(): bool
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return strpos($script, '/api/') !== false;
}

/** Friendly, consistent rejection for both API and page requests. */
function ap_reject(int $status, string $message, array $extraHeaders = []): void
{
    if (!headers_sent()) {
        http_response_code($status);
        foreach ($extraHeaders as $h) {
            header($h);
        }
    }
    $wantsJson = ap_is_api_request()
        || stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
        || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['success' => false, 'message' => $message, 'status' => $status]);
    } else {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        $GLOBALS['ap_error_status'] = $status;
        $GLOBALS['ap_error_message'] = $message;
        $errorPage = APP_ROOT . '/pages/errors/error.php';
        if (is_file($errorPage)) {
            include $errorPage;
        } else {
            echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        }
    }
    exit;
}

/* ------------------------------------------------------------- PHP / session */

@ini_set('expose_php', '0');
@ini_set('display_errors', ap_env('APP_DEBUG') === 'true' ? '1' : '0');
@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.use_trans_sid', '0');
@ini_set('session.cookie_httponly', '1');
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('session.sid_length', '48');
@ini_set('session.sid_bits_per_character', '6');
@ini_set('session.gc_maxlifetime', '86400');
if (ap_is_https()) {
    @ini_set('session.cookie_secure', '1');
}
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_name('RJITSESSID');
}

if (PHP_SAPI === 'cli') {
    return; // cron jobs and scripts: no HTTP layer below
}

/* ----------------------------------------------------- method + size guards */

$apMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($apMethod, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
    ap_reject(405, 'That request method is not allowed.', ['Allow: GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS']);
}

$apMaxBody = (int)ap_env('MAX_REQUEST_MB', 25) * 1024 * 1024;
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > $apMaxBody) {
    ap_reject(413, 'That upload is too large. Please keep files under ' . (int)ap_env('MAX_REQUEST_MB', 25) . ' MB.');
}

/* ------------------------------------------------------------------ headers */

function ap_csp(): string
{
    $extraConnect = trim((string)ap_env('CSP_CONNECT_EXTRA', ''));
    $directives = [
        "default-src 'self'",
        // Tailwind Play CDN + Lucide + inline handlers used across pages
        "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://vjs.zencdn.net https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com https://vjs.zencdn.net https://cdn.jsdelivr.net",
        "font-src 'self' data: https://fonts.gstatic.com",
        "img-src 'self' data: blob: https:",
        "media-src 'self' blob: https:",
        "connect-src 'self' https://commons.wikimedia.org https://unpkg.com" . ($extraConnect ? ' ' . $extraConnect : ''),
        "frame-src 'self' https://meet.jit.si https://www.youtube.com https://www.youtube-nocookie.com",
        "worker-src 'self' blob:",
        "manifest-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ];
    if (ap_is_https()) {
        $directives[] = 'upgrade-insecure-requests';
    }
    return implode('; ', $directives);
}

if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Permissions-Policy: camera=(self "https://meet.jit.si"), microphone=(self "https://meet.jit.si"), display-capture=(self "https://meet.jit.si"), geolocation=(), payment=(), usb=(), interest-cohort=()');
    if (ap_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    if (ap_is_api_request()) {
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; sandbox");
        header('Cache-Control: no-store');
    } else {
        header('Content-Security-Policy: ' . ap_csp());
    }
}

/* --------------------------------------------------------------------- CORS */

function ap_allowed_origin(): ?string
{
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin === '') {
        return null;
    }
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $self = [($host ? 'https://' . $host : ''), ($host ? 'http://' . $host : '')];
    $extra = array_filter(array_map('trim', explode(',', (string)ap_env('ALLOWED_ORIGINS', ''))));
    return in_array($origin, array_merge($self, $extra), true) ? $origin : null;
}

// Scripts across /api still send "Access-Control-Allow-Origin: *". This callback
// runs right before headers go out and replaces that with a strict allowlist.
header_register_callback(function () {
    $hasCors = false;
    foreach (headers_list() as $h) {
        if (stripos($h, 'Access-Control-Allow-Origin:') === 0) {
            $hasCors = true;
            break;
        }
    }
    if (!$hasCors) {
        return;
    }
    header_remove('Access-Control-Allow-Origin');
    $allowed = ap_allowed_origin();
    if ($allowed !== null) {
        header('Access-Control-Allow-Origin: ' . $allowed);
        header('Vary: Origin', false);
    } else {
        header_remove('Access-Control-Allow-Methods');
        header_remove('Access-Control-Allow-Headers');
        header_remove('Access-Control-Max-Age');
    }
});

if ($apMethod === 'OPTIONS' && ap_is_api_request()) {
    $allowed = ap_allowed_origin();
    if ($allowed === null) {
        ap_reject(403, 'Cross-origin requests are not allowed.');
    }
    header('Access-Control-Allow-Origin: ' . $allowed);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With, X-Device-Fingerprint');
    header('Access-Control-Max-Age: 600');
    http_response_code(204);
    exit;
}

/* ------------------------------------------------------------ rate limiting */

function ap_rate_dir(): string
{
    $dir = APP_ROOT . '/storage/cache/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        $dir = rtrim(sys_get_temp_dir(), '/\\') . '/rjit_ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
    }
    return $dir;
}

/**
 * Sliding-window counter. Returns seconds to wait (0 = allowed).
 * $consume=false only peeks.
 */
function ap_rate_hit(string $bucket, string $key, int $limit, int $window, bool $consume = true): int
{
    $file = ap_rate_dir() . '/' . hash('sha256', $bucket . '|' . $key) . '.json';
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return 0; // fail open: never lock users out because of a disk issue
    }
    flock($fh, LOCK_EX);
    $now = time();
    $hits = json_decode((string)stream_get_contents($fh), true);
    $hits = is_array($hits) ? array_values(array_filter($hits, fn ($t) => $t > $now - $window)) : [];
    $wait = 0;
    if (count($hits) >= $limit) {
        $wait = max(1, ($hits[0] + $window) - $now);
    } elseif ($consume) {
        $hits[] = $now;
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($hits));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $wait;
}

function ap_rate_clear(string $bucket, string $key): void
{
    @unlink(ap_rate_dir() . '/' . hash('sha256', $bucket . '|' . $key) . '.json');
}

function ap_human_wait(int $seconds): string
{
    if ($seconds < 60) {
        return $seconds . ' second' . ($seconds === 1 ? '' : 's');
    }
    $m = (int)ceil($seconds / 60);
    return $m . ' minute' . ($m === 1 ? '' : 's');
}

$apIp = ap_client_ip();
$apScript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));

if (ap_is_api_request() && ap_env('RATE_LIMIT_DISABLED') !== 'true') {
    // 1) General budget for every API call
    $wait = ap_rate_hit('api', $apIp, (int)ap_env('RATE_LIMIT_API_PER_MIN', 300), 60);
    if ($wait) {
        ap_reject(429, "You're going a little fast. Please wait " . ap_human_wait($wait) . ' and try again.', ['Retry-After: ' . $wait]);
    }

    // 2) Strict budget for credential / account endpoints
    $authScripts = [
        'login.php' => [(int)ap_env('RATE_LIMIT_LOGIN', 20), 600],
        'register.php' => [8, 3600], 'register_alumni.php' => [8, 3600],
        'register_student.php' => [8, 3600], 'register_faculty.php' => [8, 3600],
        'request_reset.php' => [5, 900], 'reset_password.php' => [10, 900],
        'change_password.php' => [10, 900], 'generate_token.php' => [20, 600],
    ];
    if (isset($authScripts[$apScript]) && $apMethod === 'POST') {
        [$limit, $window] = $authScripts[$apScript];
        $wait = ap_rate_hit('auth:' . $apScript, $apIp, $limit, $window);
        if ($wait) {
            ap_reject(429, 'Too many attempts from your network. Please try again in ' . ap_human_wait($wait) . '.', ['Retry-After: ' . $wait]);
        }
    }

    // 3) Per-account lockout after repeated wrong passwords
    if ($apScript === 'login.php' && $apMethod === 'POST') {
        $body = json_decode((string)file_get_contents('php://input'), true);
        $email = strtolower(trim((string)($body['email'] ?? $_POST['email'] ?? '')));
        if ($email !== '') {
            $maxFails = (int)ap_env('LOGIN_MAX_FAILURES', 8);
            $lockWindow = (int)ap_env('LOGIN_LOCK_SECONDS', 900);
            $wait = ap_rate_hit('loginfail', $email, $maxFails, $lockWindow, false);
            if ($wait) {
                ap_reject(429, 'This account is temporarily locked after several wrong passwords. Try again in '
                    . ap_human_wait($wait) . ', or use "Forgot password".', ['Retry-After: ' . $wait]);
            }
            register_shutdown_function(function () use ($email, $maxFails, $lockWindow) {
                $code = http_response_code();
                if ($code === 401) {
                    ap_rate_hit('loginfail', $email, $maxFails, $lockWindow);
                } elseif ($code === 200) {
                    ap_rate_clear('loginfail', $email);
                }
            });
        }
    }
}

/* ---------------------------------------------------------- upload inspection */

/**
 * Inspect every uploaded file before any script touches it. Scripts still apply
 * their own per-feature rules; this is a platform-wide safety net.
 */
function ap_inspect_uploads(): void
{
    if (empty($_FILES)) {
        return;
    }
    // extension => acceptable sniffed MIME types
    $map = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'gif' => ['image/gif'], 'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'epub' => ['application/epub+zip', 'application/zip'],
        'txt' => ['text/plain', 'application/octet-stream', 'inode/x-empty'],
        'json' => ['application/json', 'text/plain'],
        'csv' => ['text/csv', 'text/plain', 'application/csv'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'mp3' => ['audio/mpeg', 'audio/mp3'], 'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
        'mp4' => ['video/mp4'], 'webm' => ['video/webm', 'audio/webm'], 'ogg' => ['audio/ogg', 'video/ogg'],
    ];
    $danger = '/\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|bash|exe|dll|bat|cmd|com|msi|jar|js|mjs|html?|xhtml|svg|svgz|xml|htaccess|htpasswd|ini|asp|aspx|jsp)(\.|$)/i';

    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

    $walk = function ($names, $tmps, $errors) use (&$walk, $map, $danger, $finfo) {
        if (is_array($names)) {
            foreach ($names as $k => $n) {
                $walk($n, $tmps[$k] ?? null, $errors[$k] ?? null);
            }
            return;
        }
        if ($errors !== UPLOAD_ERR_OK || !$tmps || !is_uploaded_file($tmps)) {
            return;
        }
        $name = (string)$names;
        if ($name === '' || preg_match('/[\x00-\x1f]/', $name) || preg_match($danger, $name)) {
            ap_reject(415, 'That file type is not allowed for security reasons.');
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset($map[$ext])) {
            ap_reject(415, 'Files of type .' . htmlspecialchars($ext ?: 'unknown') . ' are not supported here.');
        }
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $tmps);
            if (!in_array($mime, $map[$ext], true)) {
                ap_reject(415, "That file doesn't look like a real ." . $ext . ' file. Please re-export it and try again.');
            }
        }
        // Reject server-side code hidden inside images/documents (polyglots)
        $head = (string)@file_get_contents($tmps, false, null, 0, 1024 * 1024);
        if (preg_match('/<\?php|<\?=|<script\b|__halt_compiler/i', $head)) {
            ap_reject(415, 'This file contains embedded code and was blocked.');
        }
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && @getimagesize($tmps) === false) {
            ap_reject(415, "That image couldn't be read. Please try a different file.");
        }
    };

    foreach ($_FILES as $f) {
        $walk($f['name'] ?? null, $f['tmp_name'] ?? null, $f['error'] ?? null);
    }
    if ($finfo) {
        finfo_close($finfo);
    }
}

if ($apMethod === 'POST' || $apMethod === 'PUT') {
    ap_inspect_uploads();
}

/* ------------------------------------------------------- error-leak scrubber */

/**
 * Many API scripts echo $e->getMessage() back to the client. In production,
 * replace anything that looks like an internal error (SQL, stack traces,
 * server paths) with a friendly message, and log the original server-side.
 */
if (ap_is_api_request() && ap_env('APP_DEBUG') !== 'true') {
    ob_start(function (string $buffer): string {
        $first = ltrim(substr($buffer, 0, 8));
        if ($first === '' || ($first[0] !== '{' && $first[0] !== '[')) {
            return $buffer; // binary downloads, images, etc.
        }
        $leak = '/SQLSTATE\[|PDOException|Stack trace|syntax error at|relation "|column "|Undefined (index|variable|array key)|\/var\/www|[A-Z]:\\\\(xampp|wamp)|\.php(:| on line)/i';
        if (!preg_match($leak, $buffer)) {
            return $buffer;
        }
        $data = json_decode($buffer, true);
        if (!is_array($data)) {
            return $buffer;
        }
        array_walk_recursive($data, function (&$v) use ($leak) {
            if (is_string($v) && preg_match($leak, $v)) {
                error_log('[api-error] ' . $v);
                $v = 'Something went wrong on our side. Please try again in a moment.';
            }
        });
        return (string)json_encode($data);
    });
}
