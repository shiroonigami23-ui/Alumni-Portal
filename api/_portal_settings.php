<?php
/**
 * Tiny key/value store for portal-wide settings (site banner, etc.).
 * Stored in the database so every app server sees the same value.
 */
require_once __DIR__ . '/../config/DbCompat.php';

function ensure_portal_settings(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    require_once __DIR__ . '/../helpers/Cache.php';
    $__schemaKey = 'schema:ensure_portal_settings:' . @filemtime(__FILE__) . ':' . (getenv('DB_NAME') ?: 'db');
    if (Cache::get($__schemaKey)) {
        $done = true;
        return;
    }
    $now = db_is_pgsql($db) ? 'NOW()' : 'CURRENT_TIMESTAMP';
    $db->exec("
        CREATE TABLE IF NOT EXISTS portal_settings (
            setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL,
            updated_by BIGINT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT $now
        )
    ");
    Cache::set($__schemaKey, 1, 86400);
    $done = true;
}

function portal_setting_get(PDO $db, string $key, $default = null)
{
    ensure_portal_settings($db);
    $stmt = $db->prepare("SELECT setting_value FROM portal_settings WHERE setting_key = :k");
    $stmt->execute(['k' => $key]);
    $v = $stmt->fetchColumn();
    if ($v === false || $v === null) {
        return $default;
    }
    $decoded = json_decode((string)$v, true);
    return $decoded === null ? $default : $decoded;
}

function portal_setting_set(PDO $db, string $key, $value, ?int $by = null): void
{
    ensure_portal_settings($db);
    $json = $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $db->prepare("DELETE FROM portal_settings WHERE setting_key = :k")->execute(['k' => $key]);
    if ($json !== null) {
        $db->prepare("INSERT INTO portal_settings (setting_key, setting_value, updated_by) VALUES (:k, :v, :b)")
           ->execute(['k' => $key, 'v' => $json, 'b' => $by]);
    }
}
