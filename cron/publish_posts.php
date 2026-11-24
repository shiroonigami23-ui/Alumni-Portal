<?php
/** Publish scheduled posts whose time has come. Run every minute (or via cron/run.php). */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../api/_post_scheduler.php';

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "DB unavailable\n");
    exit(1);
}
$n = publish_due_scheduled_posts($db, 200);
echo "PUBLISH_OK published=$n\n";
