<?php

require_once __DIR__ . '/_asset_store.php';

function normalize_media_url(?string $url): string
{
    $url = trim(str_replace('\\', '/', (string)$url));
    if ($url === '') {
        return '';
    }

    if (stripos($url, 'data:image/') === 0) {
        return $url;
    }

    if (stripos($url, 'via.placeholder.com') !== false || stripos($url, 'placeholder') !== false) {
        return '';
    }

    if (preg_match('#^https?://#i', $url)) {
        $parsedPath = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        if ($parsedPath !== '' && preg_match('#/api/asset\.php$#i', $parsedPath)) {
            return $url;
        }
        if ($parsedPath !== '' && (
            stripos($parsedPath, '/storage/profiles/') !== false ||
            stripos($parsedPath, '/storage/covers/') !== false
        )) {
            $query = (string)(parse_url($url, PHP_URL_QUERY) ?? '');
            return ltrim($parsedPath . ($query !== '' ? '?' . $query : ''), '/');
        }
        return $url;
    }

    return ltrim($url, '/');
}

function resolve_local_media_absolute_path(string $url): ?string
{
    $normalized = normalize_media_url($url);
    if ($normalized === '' || preg_match('#\.php(?:$|\?)#i', $normalized)) {
        return null;
    }

    $candidates = [];

    if (
        stripos($normalized, 'storage/profiles/') === 0 ||
        stripos($normalized, 'storage/covers/') === 0
    ) {
        $candidates[] = $normalized;
    } elseif (preg_match('/^[a-z0-9._-]+\.(?:jpg|jpeg|png|gif|webp|bmp|svg)$/i', basename($normalized))) {
        $basename = basename($normalized);
        $candidates[] = 'storage/profiles/' . $basename;
        $candidates[] = 'storage/covers/' . $basename;
    }

    foreach ($candidates as $candidate) {
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        if (is_file($abs)) {
            return $abs;
        }
    }

    return null;
}

function persist_profile_media_url(PDO $db, int $userId, string $column, string $url): void
{
    if (!in_array($column, ['profile_picture_url', 'cover_photo_url'], true)) {
        return;
    }

    $sql = "UPDATE profiles SET {$column} = :url, updated_at = CURRENT_TIMESTAMP WHERE user_id = :uid";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':url' => $url !== '' ? $url : null,
        ':uid' => $userId
    ]);
}

function resolve_profile_media_url(PDO $db, int $userId, ?string $url, string $column, string $scope): string
{
    $normalized = normalize_media_url($url);
    if ($normalized === '') {
        return '';
    }

    if (preg_match('#\.php(?:$|\?)#i', $normalized) || stripos($normalized, 'data:image/') === 0) {
        return $normalized;
    }

    $absolutePath = resolve_local_media_absolute_path($normalized);
    if ($absolutePath) {
        $stored = store_asset_from_path($db, $userId, $absolutePath, 'image', $scope, basename($absolutePath));
        if ($stored && !empty($stored['url'])) {
            persist_profile_media_url($db, $userId, $column, (string)$stored['url']);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            return (string)$stored['url'];
        }
    }

    if (
        stripos($normalized, 'storage/profiles/') === 0 ||
        stripos($normalized, 'storage/covers/') === 0 ||
        preg_match('/^[a-z0-9._-]+\.(?:jpg|jpeg|png|gif|webp|bmp|svg)$/i', basename($normalized))
    ) {
        persist_profile_media_url($db, $userId, $column, '');
        return '';
    }

    return $normalized;
}
