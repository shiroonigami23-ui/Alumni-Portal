<?php

include_once '../config/Database.php';
include_once __DIR__ . '/_asset_store.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    http_response_code(404);
    exit;
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    http_response_code(500);
    exit;
}

ensure_asset_store_schema($db);

$stmt = $db->prepare("
    SELECT original_name, mime_type, asset_kind, binary_data, file_size
    FROM uploaded_assets
    WHERE public_token = :token
    LIMIT 1
");
$stmt->execute([':token' => $token]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    http_response_code(404);
    exit;
}

$mimeType = trim((string)($asset['mime_type'] ?? 'application/octet-stream')) ?: 'application/octet-stream';
$originalName = (string)($asset['original_name'] ?? 'download');
$binary = $asset['binary_data'];
$size = (int)($asset['file_size'] ?? 0);
$kind = (string)($asset['asset_kind'] ?? 'file');

// Never let an uploaded file render as active content (HTML/SVG/XML/JS) on our origin
$inlineSafe = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'audio/mpeg', 'audio/mp4', 'video/mp4', 'video/webm'];
if (preg_match('#(html|xml|svg|javascript|ecmascript|x-php)#i', $mimeType) || !preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', $mimeType)) {
    $mimeType = 'application/octet-stream';
}
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . ($size > 0 ? $size : strlen((string)$binary)));
header('Cache-Control: public, max-age=31536000, immutable');

$disposition = (in_array($kind, ['image', 'gif'], true) && in_array($mimeType, $inlineSafe, true)) ? 'inline' : 'attachment';
$safeName = str_replace(["\r", "\n", '"', '\\'], '', $originalName);
$asciiName = preg_replace('/[^\x20-\x7E]/', '_', $safeName) ?: 'download';
// ASCII fallback + UTF-8 name (RFC 5987) so "रिज़्यूमे.pdf" downloads with its real name.
header('Content-Disposition: ' . $disposition . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($safeName));

echo is_resource($binary) ? stream_get_contents($binary) : $binary;
