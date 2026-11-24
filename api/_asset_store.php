<?php

function ensure_asset_store_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    require_once __DIR__ . '/../helpers/Cache.php';
    $__schemaKey = 'schema:ensure_asset_store_schema:' . @filemtime(__FILE__) . ':' . (getenv('DB_NAME') ?: 'db');
    if (Cache::get($__schemaKey)) {
        $done = true;
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS uploaded_assets (
            asset_id BIGSERIAL PRIMARY KEY,
            public_token VARCHAR(64) UNIQUE NOT NULL,
            owner_user_id BIGINT NOT NULL,
            scope VARCHAR(32) NOT NULL,
            asset_kind VARCHAR(16) NOT NULL,
            original_name TEXT,
            mime_type TEXT,
            file_ext VARCHAR(32),
            file_size BIGINT DEFAULT 0,
            binary_data BYTEA NOT NULL,
            created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
        )
    ");

    Cache::set($__schemaKey, 1, 86400);
    $done = true;
}

/**
 * The user's file name is only a display label: strip folders, control characters and anything that could
 * confuse a download header, cap its length, and make sure it keeps a sensible extension.
 */
function asset_clean_name(string $name, string $ext = ''): string
{
    $name = str_replace('\\', '/', $name);
    $name = basename($name);
    $name = preg_replace('/[\x00-\x1F\x7F"<>|*?:]+/u', '', $name) ?? '';
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '', " .\t");
    if ($name === '') {
        $name = 'file';
    }
    if (mb_strlen($name) > 150) {
        $dot = strrpos($name, '.');
        $tail = $dot !== false ? substr($name, $dot) : '';
        $name = mb_substr($name, 0, 150 - mb_strlen($tail)) . $tail;
    }
    if ($ext !== '' && strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== strtolower($ext)) {
        $name .= '.' . $ext;
    }
    return $name;
}

function insert_binary_asset(
    PDO $db,
    int $ownerUserId,
    string $scope,
    string $kind,
    string $original,
    string $mimeType,
    string $safeExt,
    int $size,
    string $binary
): ?array {
    ensure_asset_store_schema($db);
    $original = asset_clean_name($original, $safeExt);

    // 192-bit random token under a UNIQUE key: storage names can never collide, whatever the user called the file.
    // (Retry is belt-and-braces; a clash is ~1 in 10^57.)
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $token = bin2hex(random_bytes(24));
        $dup = $db->prepare("SELECT 1 FROM uploaded_assets WHERE public_token = :t");
        $dup->execute([':t' => $token]);
        if (!$dup->fetchColumn()) {
            break;
        }
    }
    $stmt = $db->prepare("
        INSERT INTO uploaded_assets
            (public_token, owner_user_id, scope, asset_kind, original_name, mime_type, file_ext, file_size, binary_data)
        VALUES
            (:token, :owner_user_id, :scope, :asset_kind, :original_name, :mime_type, :file_ext, :file_size, :binary_data)
        RETURNING asset_id
    ");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->bindValue(':owner_user_id', $ownerUserId, PDO::PARAM_INT);
    $stmt->bindValue(':scope', $scope, PDO::PARAM_STR);
    $stmt->bindValue(':asset_kind', $kind, PDO::PARAM_STR);
    $stmt->bindValue(':original_name', $original, PDO::PARAM_STR);
    $stmt->bindValue(':mime_type', $mimeType, PDO::PARAM_STR);
    $stmt->bindValue(':file_ext', $safeExt, PDO::PARAM_STR);
    $stmt->bindValue(':file_size', $size, PDO::PARAM_INT);
    $stmt->bindValue(':binary_data', $binary, PDO::PARAM_LOB);
    $stmt->execute();
    $assetId = (int)($stmt->fetchColumn() ?: 0);

    return [
        'asset_id' => $assetId,
        'type' => $kind,
        'url' => 'api/asset.php?token=' . rawurlencode($token),
        'name' => $original,
        'mime_type' => $mimeType,
        'size' => $size
    ];
}

function store_uploaded_asset(PDO $db, int $ownerUserId, array $file, string $kind, string $scope = 'post'): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return null;
    }

    $original = (string)($file['name'] ?? 'file');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext);
    if ($safeExt === '') {
        $safeExt = 'bin';
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string)(finfo_file($finfo, $tmpPath) ?: '');
            finfo_close($finfo);
        }
    }
    if ($mimeType === '') {
        $mimeType = (string)($file['type'] ?? 'application/octet-stream');
    }

    $binary = file_get_contents($tmpPath);
    if ($binary === false) {
        return null;
    }

    $size = (int)($file['size'] ?? strlen($binary));
    return insert_binary_asset($db, $ownerUserId, $scope, $kind, $original, $mimeType, $safeExt, $size, $binary);
}

function store_asset_from_path(PDO $db, int $ownerUserId, string $absolutePath, string $kind, string $scope = 'post', ?string $originalName = null): ?array
{
    if (!is_file($absolutePath)) {
        return null;
    }

    $binary = @file_get_contents($absolutePath);
    if ($binary === false) {
        return null;
    }

    $original = $originalName ?: basename($absolutePath);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext);
    if ($safeExt === '') {
        $safeExt = 'bin';
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string)(finfo_file($finfo, $absolutePath) ?: '');
            finfo_close($finfo);
        }
    }
    if ($mimeType === '') {
        $mimeType = 'application/octet-stream';
    }

    return insert_binary_asset($db, $ownerUserId, $scope, $kind, $original, $mimeType, $safeExt, filesize($absolutePath) ?: strlen($binary), $binary);
}

function delete_asset_by_url(PDO $db, ?string $url): void
{
    $url = trim((string)$url);
    if ($url === '') {
        return;
    }

    $token = '';
    if (preg_match('/[?&]token=([^&]+)/', $url, $matches)) {
        $token = rawurldecode((string)$matches[1]);
    }

    if ($token === '') {
        return;
    }

    ensure_asset_store_schema($db);
    $stmt = $db->prepare("DELETE FROM uploaded_assets WHERE public_token = :token");
    $stmt->execute([':token' => $token]);
}
