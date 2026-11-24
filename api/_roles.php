<?php
/**
 * Role of a user, without a query when it's the signed-in caller (the kernel's auth
 * query already loaded it) and at most one query per other user per request.
 * Every endpoint and helper asks here instead of running its own "SELECT role".
 */
function api_user_role(PDO $db, int $uid): string
{
    static $memo = [];
    if ($uid <= 0) {
        return '';
    }
    $ctx = $GLOBALS['__api_ctx'] ?? null;   // set by the API kernel; absent in cron / CLI
    if (is_object($ctx) && (int)($ctx->uid ?? 0) === $uid && (string)($ctx->role ?? '') !== '') {
        return $ctx->role;
    }
    if (!isset($memo[$uid])) {
        $s = $db->prepare("SELECT role FROM users WHERE user_id = :u");
        $s->execute(['u' => $uid]);
        $memo[$uid] = strtolower((string)($s->fetchColumn() ?: ''));
    }
    return $memo[$uid];
}

/** Drop-in for the old `prepare("SELECT role …"); execute()` pair: same fetch API, no round trip. */
final class ApiRoleRow
{
    public function __construct(private string $role) {}
    public function fetchColumn() { return $this->role === '' ? false : $this->role; }
    public function fetch($mode = null) { return $this->role === '' ? false : ['role' => $this->role]; }
}
function api_role_stmt(PDO $db, $uid): ApiRoleRow
{
    return new ApiRoleRow(api_user_role($db, (int)$uid));
}
