<?php
/**
 * API kernel — the one front door every endpoint goes through.
 *
 *     require_once __DIR__ . '/_kernel.php';
 *     $api = api_endpoint([
 *         'access'  => 'member',                 // who may call this (see API_ACCESS_LEVELS)
 *         'methods' => ['GET', 'POST'],          // optional; anything else gets 405
 *         'actions' => ['approve' => 'admin'],   // optional; stricter access for ?action=…
 *     ]);
 *     $postId = $api->int('post_id', required: true);
 *     ...
 *     api_ok(['post' => $post], 'Saved.');
 *
 * Why: before this, each of ~120 files did its own login check, role check, input parsing and error
 * output. One file forgot the role check and any member could ban the admin. Now the access level is a
 * declaration the kernel enforces *before* the endpoint's own code runs, CI fails any endpoint that
 * doesn't declare one (tests/static/api_contract.php), and the authorization sweep reads these
 * declarations to attack every non-public endpoint automatically.
 *
 * Access levels
 *   public     anyone; if a valid token is sent, $api->uid is set (optional sign-in)
 *   member     any signed-in, non-banned account
 *   staff      faculty or admin
 *   admin      admin only
 *   moderator  admin, or a member with the moderator flag
 *   ['alumni', 'faculty', 'admin']   any of the listed roles
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

const API_ACCESS_LEVELS = ['public', 'member', 'staff', 'admin', 'moderator'];
const API_ROLES = ['student', 'alumni', 'faculty', 'admin'];

final class ApiContext
{
    /** @var PDO */
    public $db;
    /** @var Auth */
    public $auth;
    public int $uid = 0;
    public string $role = '';
    public bool $isModerator = false;
    public string $method = 'GET';
    public string $action = '';
    /** Request parameters: JSON body (or form fields) merged over the query string. */
    public array $input = [];

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->input) && $this->input[$key] !== null && $this->input[$key] !== '';
    }

    /** Whole number ≥ 0 (ids, counts). Missing → $default, or 400 when $required. */
    public function int(string $key, bool $required = false, int $default = 0, int $max = PHP_INT_MAX): int
    {
        if (!$this->has($key)) {
            if ($required) {
                api_fail(400, "$key is required.");
            }
            return $default;
        }
        $v = $this->input[$key];
        if (is_int($v) || (is_string($v) && preg_match('/^\d{1,18}$/', trim($v)))) {
            return min((int)$v, $max);
        }
        api_fail(400, "$key must be a whole number.");
    }

    /** Trimmed single-line or multi-line text, capped at $max characters. */
    public function str(string $key, int $max = 1000, bool $required = false, string $default = ''): string
    {
        if (!$this->has($key)) {
            if ($required) {
                api_fail(400, "$key is required.");
            }
            return $default;
        }
        $v = $this->input[$key];
        if (is_array($v) || is_object($v)) {
            api_fail(400, "$key must be text.");
        }
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$v) ?? '');
        if (mb_strlen($v) > $max) {
            api_fail(422, "$key is too long (max $max characters).");
        }
        if ($required && $v === '') {
            api_fail(400, "$key is required.");
        }
        return $v;
    }

    /** true/false from JSON booleans, 1/0, "true"/"false", "on"/"off", "yes"/"no". */
    public function bool(string $key, ?bool $default = null): ?bool
    {
        if (!array_key_exists($key, $this->input)) {
            return $default;
        }
        $v = $this->input[$key];
        if (is_bool($v)) {
            return $v;
        }
        $b = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($b === null) {
            api_fail(400, "$key must be true or false.");
        }
        return $b;
    }

    /** One of a fixed list of values (sort orders, statuses…). */
    public function oneOf(string $key, array $allowed, ?string $default = null): ?string
    {
        if (!$this->has($key)) {
            return $default;
        }
        $v = strtolower(trim((string)$this->input[$key]));
        if (!in_array($v, $allowed, true)) {
            api_fail(400, "$key must be one of: " . implode(', ', $allowed) . '.');
        }
        return $v;
    }

    public function isStaff(): bool
    {
        return $this->role === 'admin' || $this->role === 'faculty';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Write to the audit log; never breaks the request if logging fails. */
    public function log(string $action, string $details): void
    {
        try {
            $this->auth->logAction($this->uid, $action, $details);
        } catch (Throwable $e) {
            error_log("audit log failed ($action): " . $e->getMessage());
        }
    }
}

/** Send JSON and stop. */
function api_send(int $code, array $body): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Success: { success: true, status: "success", message?, data?, ...extra } */
function api_ok($data = null, ?string $message = null, array $extra = [], int $code = 200): void
{
    $body = ['success' => true, 'status' => 'success'];
    if ($message !== null) {
        $body['message'] = $message;
    }
    if ($data !== null) {
        $body['data'] = $data;
    }
    api_send($code, $body + $extra);
}

/** Failure: { success: false, status: "error", message, ...extra } — the message is shown to people, so keep it human. */
function api_fail(int $code, string $message, array $extra = []): void
{
    api_send($code, ['success' => false, 'status' => 'error', 'message' => $message] + $extra);
}

/** For endpoints that were replaced: keep the URL answering with a pointer instead of a 404. */
function api_retired(string $replacement, string $why = ''): void
{
    api_send(410, [
        'success' => false,
        'status' => 'error',
        'message' => 'This endpoint was retired.' . ($why !== '' ? " $why" : '') . " Use api/$replacement instead.",
        'replacement' => "api/$replacement",
    ]);
}

/** The access level an endpoint declared (read by tests through the X-Api-Access header in debug, and by the contract scan). */
function api_access_label($access): string
{
    return is_array($access) ? implode('|', $access) : (string)$access;
}

/**
 * Declare and enforce who may call this endpoint. Returns the request context.
 */
function api_endpoint(array $opts): ApiContext
{
    $access = $opts['access'] ?? null;
    $valid = is_array($access)
        ? ($access !== [] && !array_diff($access, API_ROLES))
        : in_array($access, API_ACCESS_LEVELS, true);
    if (!$valid) {
        error_log('api_endpoint: invalid access declaration in ' . ($_SERVER['SCRIPT_NAME'] ?? '?'));
        api_fail(500, 'This endpoint is misconfigured.');
    }

    // Unexpected errors: log the detail, show people a calm message, never a stack trace or SQL.
    set_exception_handler(function (Throwable $e) {
        error_log('[api] ' . basename((string)($_SERVER['SCRIPT_NAME'] ?? '?')) . ': ' . get_class($e) . ': ' . $e->getMessage()
            . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        api_fail(500, 'Something went wrong on our side. Please try again.');
    });

    $ctx = new ApiContext();
    $ctx->method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!empty($opts['methods']) && $ctx->method !== 'OPTIONS' && !in_array($ctx->method, array_map('strtoupper', $opts['methods']), true)) {
        header('Allow: ' . implode(', ', array_map('strtoupper', $opts['methods'])));
        api_fail(405, 'Method not allowed.');
    }

    // Parameters: query string, then form fields, then JSON body (later wins).
    $input = $_GET;
    if ($_POST) {
        $input = array_merge($input, $_POST);
    }
    if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false) {
        $json = json_decode((string)file_get_contents('php://input'), true);
        if (is_array($json)) {
            $input = array_merge($input, $json);
        }
    }
    $ctx->input = $input;
    $ctx->action = strtolower(trim((string)(is_scalar($input['action'] ?? null) ? $input['action'] : '')));

    $ctx->db = (new Database())->getConnection();
    if (!$ctx->db) {
        api_fail(503, 'The database is unavailable right now. Please try again shortly.');
    }
    $ctx->auth = new Auth($ctx->db);

    // Per-action overrides are always at least as strict as the endpoint's own level.
    $effective = $access;
    if ($ctx->action !== '' && isset($opts['actions'][$ctx->action])) {
        $effective = $opts['actions'][$ctx->action];
    }

    $who = $ctx->auth->authenticate();
    if ($who['ok']) {
        $ctx->uid = (int)$who['user_id'];
        $ctx->role = (string)($who['role'] ?? '');          // from the same single auth query — no extra round-trip
        $ctx->isModerator = (bool)($who['is_moderator'] ?? false);
    } elseif ($effective !== 'public') {
        $extra = !empty($who['is_shadow_banned']) ? ['is_shadow_banned' => true] : [];
        api_fail($who['code'], $who['message'], $extra);
    }

    if (!api_role_allows($effective, $ctx->role, $ctx->isModerator)) {
        api_fail(403, api_denied_message($effective));
    }

    return $ctx;
}

function api_role_allows($access, string $role, bool $isModerator): bool
{
    if (is_array($access)) {
        return in_array($role, $access, true);
    }
    switch ($access) {
        case 'public':
            return true;
        case 'member':
            return $role !== '';
        case 'staff':
            return $role === 'admin' || $role === 'faculty';
        case 'admin':
            return $role === 'admin';
        case 'moderator':
            return $role === 'admin' || $isModerator;
    }
    return false;
}

function api_denied_message($access): string
{
    if (is_array($access)) {
        return 'This is only available to ' . implode(', ', $access) . ' accounts.';
    }
    return [
        'staff' => 'This is only available to faculty and admins.',
        'admin' => 'Admin access only.',
        'moderator' => 'Only admins and moderators can do this.',
    ][$access] ?? 'You don\'t have access to this.';
}

/**
 * Log an unexpected error and return a short reference (ERR-3f9a2c1b) that a person can quote to an admin,
 * who finds the details with: grep ERR-3f9a2c1b <php error log>.
 */
function api_error_ref(Throwable $e): string
{
    $ref = 'ERR-' . bin2hex(random_bytes(4));
    error_log("[$ref] " . basename((string)($_SERVER['SCRIPT_NAME'] ?? '?')) . ': ' . get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    return $ref;
}

/**
 * A message that is safe to show people. Messages we wrote ourselves (throw new Exception("Comments are off"))
 * pass through; database/PHP errors (PDOException, TypeError, anything mentioning SQL) are replaced by
 * $fallback plus a reference code, and their details go to the server log only.
 */
function api_error_message(Throwable $e, string $fallback = 'Something went wrong on our side. Please try again.'): string
{
    $msg = $e->getMessage();
    $internal = $e instanceof PDOException || $e instanceof Error
        || preg_match('/SQLSTATE|syntax error|relation "|column "|constraint|duplicate key|PDO|Undefined (index|array key|variable)|on line \d+|\.php/i', $msg);
    if (!$internal && $msg !== '' && mb_strlen($msg) <= 300) {
        return $msg;
    }
    return rtrim($fallback, '. ') . '. (ref ' . api_error_ref($e) . ')';
}
