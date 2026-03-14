<?php

function ensure_content_store_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
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

function load_content_payload(PDO $db, ?string $pointer): array
{
    $pointer = trim((string)$pointer);
    if ($pointer === '') {
        return ['content' => '', 'attachments' => []];
    }

    $token = extract_content_token($pointer);
    if ($token !== null) {
        ensure_content_store_schema($db);
        $stmt = $db->prepare("SELECT payload FROM content_payloads WHERE public_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $rawPayload = $stmt->fetchColumn();
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
