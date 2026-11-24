<?php

function ensure_content_store_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    require_once __DIR__ . '/../helpers/Cache.php';
    $__schemaKey = 'schema:ensure_content_store_schema:' . @filemtime(__FILE__) . ':' . (getenv('DB_NAME') ?: 'db');
    if (Cache::get($__schemaKey)) {
        $done = true;
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS content_payloads (
            content_id BIGSERIAL PRIMARY KEY,
            public_token VARCHAR(64) UNIQUE NOT NULL,
            owner_user_id BIGINT NULL,
            scope VARCHAR(32) NOT NULL,
            payload JSONB NOT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    Cache::set($__schemaKey, 1, 86400);
    $done = true;
}

function make_content_pointer(string $token): string
{
    return 'dbjson:' . $token;
}

function extract_content_token(?string $pointer): ?string
{
    $value = trim((string)$pointer);
    if ($value === '' || stripos($value, 'dbjson:') !== 0) {
        return null;
    }

    $token = substr($value, 7);
    return $token !== '' ? $token : null;
}

function store_content_payload(PDO $db, ?int $ownerUserId, array $payload, string $scope = 'post'): string
{
    ensure_content_store_schema($db);

    $token = bin2hex(random_bytes(24));
    $stmt = $db->prepare("
        INSERT INTO content_payloads (public_token, owner_user_id, scope, payload)
        VALUES (:token, :owner_user_id, :scope, CAST(:payload AS JSONB))
    ");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    if ($ownerUserId === null) {
        $stmt->bindValue(':owner_user_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':owner_user_id', $ownerUserId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':scope', $scope, PDO::PARAM_STR);
    $stmt->bindValue(':payload', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
    $stmt->execute();

    return make_content_pointer($token);
}

/**
 * Load many payloads in ONE query (lists: feed, comments, replies). Afterwards load_content_payload() answers
 * from memory for these pointers instead of one database round trip per item.
 */
function prefetch_content_payloads(PDO $db, array $pointers): void
{
    $tokens = [];
    foreach ($pointers as $p) {
        $t = extract_content_token(trim((string)$p));
        if ($t !== null && !array_key_exists($t, $GLOBALS['__content_prefetch'] ?? [])) {
            $tokens[$t] = true;
        }
    }
    if (!$tokens) {
        return;
    }
    ensure_content_store_schema($db);
    foreach (array_chunk(array_keys($tokens), 500) as $chunk) {
        $ph = [];
        $params = [];
        foreach ($chunk as $i => $t) {
            $ph[] = ":t$i";
            $params[":t$i"] = $t;
        }
        $st = $db->prepare("SELECT public_token, payload FROM content_payloads WHERE public_token IN (" . implode(',', $ph) . ")");
        $st->execute($params);
        foreach ($chunk as $t) {
            $GLOBALS['__content_prefetch'][$t] = false;      // missing rows answer "empty" without another query
        }
        while ($r = $st->fetch(PDO::FETCH_NUM)) {
            $GLOBALS['__content_prefetch'][$r[0]] = $r[1];
        }
    }
}

function load_content_payload(PDO $db, ?string $pointer): array
{
    $pointer = trim((string)$pointer);
    if ($pointer === '') {
        return ['content' => '', 'attachments' => []];
    }

    $token = extract_content_token($pointer);
    if ($token !== null) {
        if (array_key_exists($token, $GLOBALS['__content_prefetch'] ?? [])) {
            $rawPayload = $GLOBALS['__content_prefetch'][$token];   // loaded by prefetch_content_payloads()
        } else {
            ensure_content_store_schema($db);
            $stmt = $db->prepare("SELECT payload FROM content_payloads WHERE public_token = :token LIMIT 1");
            $stmt->execute([':token' => $token]);
            $rawPayload = $stmt->fetchColumn();
        }
        $decoded = json_decode((string)$rawPayload, true);
        if (is_array($decoded)) {
            return [
                'content' => (string)($decoded['content'] ?? ''),
                'attachments' => is_array($decoded['attachments'] ?? null) ? $decoded['attachments'] : []
            ];
        }
        return ['content' => '', 'attachments' => []];
    }

    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pointer);
    if (!is_file($abs)) {
        return ['content' => '', 'attachments' => []];
    }

    $raw = file_get_contents($abs);
    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded) && array_key_exists('content', $decoded)) {
        return [
            'content' => (string)($decoded['content'] ?? ''),
            'attachments' => is_array($decoded['attachments'] ?? null) ? $decoded['attachments'] : []
        ];
    }

    return [
        'content' => (string)$raw,
        'attachments' => []
    ];
}

function update_content_payload(PDO $db, ?string $pointer, array $payload, ?int $ownerUserId = null, string $scope = 'post'): string
{
    $pointer = trim((string)$pointer);
    $token = extract_content_token($pointer);
    if ($token !== null) {
        unset($GLOBALS['__content_prefetch'][$token]);   // never serve a stale in-memory copy
    }
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($token !== null) {
        ensure_content_store_schema($db);
        $stmt = $db->prepare("
            UPDATE content_payloads
            SET payload = CAST(:payload AS JSONB), updated_at = CURRENT_TIMESTAMP
            WHERE public_token = :token
        ");
        $stmt->execute([
            ':payload' => $json,
            ':token' => $token
        ]);
        if ($stmt->rowCount() > 0) {
            return $pointer;
        }
    }

    if ($pointer !== '') {
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pointer);
        if (is_file($abs)) {
            file_put_contents($abs, $json);
            return $pointer;
        }
    }

    return store_content_payload($db, $ownerUserId, $payload, $scope);
}

function delete_content_payload(PDO $db, ?string $pointer): void
{
    $pointer = trim((string)$pointer);
    if ($pointer === '') {
        return;
    }

    $token = extract_content_token($pointer);
    if ($token !== null) {
        unset($GLOBALS['__content_prefetch'][$token]);
        ensure_content_store_schema($db);
        $stmt = $db->prepare("DELETE FROM content_payloads WHERE public_token = :token");
        $stmt->execute([':token' => $token]);
        return;
    }

    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pointer);
    if (is_file($abs)) {
        @unlink($abs);
    }
}

function delete_content_payload_batch(PDO $db, array $pointers): void
{
    $seen = [];
    foreach ($pointers as $pointer) {
        $clean = trim((string)$pointer);
        if ($clean === '' || isset($seen[$clean])) {
            continue;
        }
        $seen[$clean] = true;
        delete_content_payload($db, $clean);
    }
}

/**
 * GIFs come from the GIF picker (Wikimedia Commons) — accept only https links to known GIF hosts, with no
 * characters that could break out of an HTML attribute. Returns '' for anything else.
 */
function safe_gif_url(?string $url): string
{
    $url = trim((string)$url);
    if ($url === '' || strlen($url) > 600 || preg_match('/[\s"\'<>\\\\`]/', $url)) {
        return '';
    }
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
        return '';
    }
    $host = strtolower($parts['host']);
    $allowed = ['upload.wikimedia.org', 'commons.wikimedia.org', 'media.giphy.com', 'i.giphy.com', 'media.tenor.com', 'c.tenor.com'];
    foreach ($allowed as $ok) {
        if ($host === $ok || substr($host, -strlen('.' . $ok)) === '.' . $ok) {
            return $url;
        }
    }
    return '';
}
