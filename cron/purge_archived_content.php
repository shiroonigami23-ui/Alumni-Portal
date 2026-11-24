<?php
/**
 * Permanently delete stored content of posts archived > 30 days ago.
 *   php cron/purge_archived_content.php            dry run
 *   php cron/purge_archived_content.php --apply    do it
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/lib_archive.php';
$o = getopt('', ['apply']);
$n = purge_archived_content((new Database())->getConnection(), isset($o['apply']));
echo isset($o['apply']) ? "PURGE_OK purged=$n\n" : "DRY RUN: $n archived item(s) would be purged. Re-run with --apply.\n";
