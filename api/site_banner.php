<?php
/**
 * Public: the current site-wide banner set by admins (or null).
 * Shown at the top of every page by assets/js/variety-ui.js.
 */
header("Content-Type: application/json; charset=UTF-8");
header('Cache-Control: public, max-age=60');

include_once '../config/Database.php';
include_once __DIR__ . '/_portal_settings.php';
include_once __DIR__ . '/../helpers/Cache.php';

$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['success' => true, 'data' => null]);
    exit;
}
try {
    $b = Cache::remember('site_banner', 60, fn () => portal_setting_get($db, 'site_banner'));
    if (!is_array($b) || empty($b['message']) || (!empty($b['expires_at']) && strtotime($b['expires_at']) < time())) {
        $b = null;
    }
    echo json_encode(['success' => true, 'data' => $b]);
} catch (Throwable $e) {
    error_log('site_banner: ' . $e->getMessage());
    echo json_encode(['success' => true, 'data' => null]);
}
