<?php

function ensure_asset_store_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
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

    $done = true;
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

    ensure_asset_store_schema($db);

    $token = bin2hex(random_bytes(24));
    $size = (int)($file['size'] ?? strlen($binary));

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
