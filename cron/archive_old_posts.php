<?php
/**
 * Move very old, inactive posts out of the live feed.
 *   php cron/archive_old_posts.php                 dry run (shows how many)
 *   php cron/archive_old_posts.php --apply         do it
 *   php cron/archive_old_posts.php --days=730      custom age (default ARCHIVE_AFTER_DAYS or 365, minimum 30)
 * See cron/lib_archive.php for the safety rules.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/lib_archive.php';
$o = getopt('', ['apply', 'days:']);
$days = (int)($o['days'] ?? (getenv('ARCHIVE_AFTER_DAYS') ?: 365));
$db = (new Database())->getConnection();
$n = archive_old_posts($db, $days, isset($o['apply']));
echo isset($o['apply']) ? "ARCHIVE_OK archived=$n\n" : "DRY RUN: $n post(s) older than $days days would be archived. Re-run with --apply.\n";
