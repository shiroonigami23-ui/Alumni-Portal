<?php

require_once __DIR__ . '/_content_store.php';

function normalize_message_attachment_from_payload(array $attachments): ?array
{
    if (empty($attachments)) {
        return null;
    }

    $first = $attachments[0] ?? null;
    if (!is_array($first) || empty($first['url'])) {
        return null;
    }

    return [
        'url' => str_replace('\\', '/', (string)$first['url']),
        'type' => isset($first['type']) ? (string)$first['type'] : null,
        'name' => isset($first['name']) ? (string)$first['name'] : null,
    ];
}

function build_message_store_payload(string $messageText, ?array $attachment = null): array
{
    $payload = [
        'content' => $messageText,
        'attachments' => [],
    ];

    if ($attachment && !empty($attachment['url'])) {
        $payload['attachments'][] = [
            'url' => str_replace('\\', '/', (string)$attachment['url']),
            'type' => isset($attachment['type']) ? (string)$attachment['type'] : null,
            'name' => isset($attachment['name']) ? (string)$attachment['name'] : null,
        ];
    }

    return $payload;
}

function read_legacy_message_payload_file(string $pointer): array
{
    $clean = str_replace(['\\', "\0"], ['/', ''], trim((string)$pointer));
    if ($clean === '') {
        return ['message' => '', 'attachment' => null, 'missing' => true, 'source' => 'missing'];
    }

    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (!is_file($absolutePath)) {
        return ['message' => '', 'attachment' => null, 'missing' => true, 'source' => 'missing'];
    }

    $raw = @file_get_contents($absolutePath);
    if ($raw === false) {
        return ['message' => '', 'attachment' => null, 'missing' => true, 'source' => 'missing'];
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $attachment = is_array($decoded['attachment'] ?? null) ? $decoded['attachment'] : null;
        if ($attachment && !empty($attachment['url'])) {
            $attachment['url'] = str_replace('\\', '/', (string)$attachment['url']);
        }
        return [
            'message' => (string)($decoded['message'] ?? ''),
            'attachment' => $attachment,
            'missing' => false,
            'source' => 'file',
        ];
    }

    return [
        'message' => (string)$raw,
        'attachment' => null,
        'missing' => false,
        'source' => 'file',
    ];
}

function load_message_payload_record(PDO $db, ?string $pointer): array
{
    $pointer = trim((string)$pointer);
    if ($pointer === '') {
        return ['message' => '', 'attachment' => null, 'missing' => true, 'source' => 'missing'];
    }

    if (extract_content_token($pointer) !== null) {
        $payload = load_content_payload($db, $pointer);
        return [
            'message' => (string)($payload['content'] ?? ''),
            'attachment' => normalize_message_attachment_from_payload((array)($payload['attachments'] ?? [])),
            'missing' => false,
            'source' => 'db',
        ];
    }

    return read_legacy_message_payload_file($pointer);
}

function store_message_payload_record(PDO $db, ?int $ownerUserId, string $messageText, ?array $attachment = null): string
{
    return store_content_payload($db, $ownerUserId, build_message_store_payload($messageText, $attachment), 'message');
}

function update_message_payload_record(PDO $db, ?string $pointer, string $messageText, ?array $attachment = null, ?int $ownerUserId = null): string
{
    return update_content_payload($db, $pointer, build_message_store_payload($messageText, $attachment), $ownerUserId, 'message');
}

function maybe_migrate_message_payload(PDO $db, string $table, int $messageId, ?int $ownerUserId, ?string $pointer, ?array $loaded = null): string
{
    $pointer = trim((string)$pointer);
    if ($pointer === '' || extract_content_token($pointer) !== null || $messageId <= 0) {
        return $pointer;
    }

    $loaded = $loaded ?: load_message_payload_record($db, $pointer);
    if (!empty($loaded['missing'])) {
        return $pointer;
    }

    $nextPointer = store_message_payload_record(
        $db,
        $ownerUserId,
        (string)($loaded['message'] ?? ''),
        is_array($loaded['attachment'] ?? null) ? $loaded['attachment'] : null
    );

    $stmt = $db->prepare("UPDATE {$table} SET content_file_path = :next WHERE message_id = :mid AND content_file_path = :old");
    $stmt->execute([
        ':next' => $nextPointer,
        ':mid' => $messageId,
        ':old' => $pointer,
    ]);

    return $nextPointer;
}
