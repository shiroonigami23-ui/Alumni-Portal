<?php
/**
 * Single scheduler entry point — run it every minute:
 *
 *   Linux / Docker:   * * * * * php /var/www/html/cron/run.php >> /var/log/rjit-cron.log 2>&1
 *   Windows (XAMPP):  scripts\run_scheduler.bat  (Task Scheduler, every 1 minute)
 *   docker-compose:   the "scheduler" service runs this in a loop
 *
 * Jobs:
 *   publish   every run      scheduled posts → feed (cron/publish_posts.php logic)
 *   promote   daily  02:00   students → alumni after graduation
 *   purge     daily  03:00   drop stored content of archived posts after 30 days
 *   archive   weekly (Sun)   ONLY if ARCHIVE_ENABLED=true — see cron/archive_old_posts.php
 *
 * Each job is guarded by a lock so overlapping runs never double-process.
 * Use `php cron/run.php --job=promote` to run one job now.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/Cache.php';
require_once __DIR__ . '/../helpers/StudentLifecycleHelper.php';
require_once __DIR__ . '/../api/_post_scheduler.php';
require_once __DIR__ . '/lib_archive.php';

date_default_timezone_set('Asia/Kolkata');
$opts = getopt('', ['job:']);
$only = $opts['job'] ?? null;
$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, '[' . date('c') . "] DB unavailable\n");
    exit(1);
}

$jobs = [
    'publish' => ['every' => 'minute', 'run' => fn () => 'published=' . publish_due_scheduled_posts($db, 200)],
    'promote' => ['every' => 'daily@02', 'run' => function () use ($db) {
        $db->beginTransaction();
        $n = StudentLifecycleHelper::promoteEligibleStudents($db);
        $db->commit();
        return "promoted=$n";
    }],
    'purge' => ['every' => 'daily@03', 'run' => fn () => 'purged=' . purge_archived_content($db, true)],
    'archive' => ['every' => 'weekly@04', 'run' => function () use ($db) {
        if (getenv('ARCHIVE_ENABLED') !== 'true') {
            return 'skipped (ARCHIVE_ENABLED is not true)';
        }
        return 'archived=' . archive_old_posts($db, (int)(getenv('ARCHIVE_AFTER_DAYS') ?: 365), true);
    }],
];

$due = function (string $every, string $name): bool {
    if ($every === 'minute') {
        return true;
    }
    [$period, $hour] = explode('@', $every);
    $stamp = $period === 'daily' ? date('Y-m-d') : date('o-W');
    if ((int)date('G') < (int)$hour || ($period === 'weekly' && date('N') !== '7')) {
        return false;
    }
    return Cache::get("cron:done:$name") !== $stamp;
};

foreach ($jobs as $name => $job) {
    if ($only && $only !== $name) {
        continue;
    }
    if (!$only && !$due($job['every'], $name)) {
        continue;
    }
    if (Cache::get("cron:lock:$name")) {
        echo '[' . date('c') . "] $name: already running, skipped\n";
        continue;
    }
    Cache::set("cron:lock:$name", 1, 600);
    $t = microtime(true);
    try {
        $result = ($job['run'])();
        if ($job['every'] !== 'minute') {
            Cache::set("cron:done:$name", strpos($job['every'], 'daily') === 0 ? date('Y-m-d') : date('o-W'), 8 * 86400);
        }
        Cache::set("cron:last:$name", ['at' => date('c'), 'result' => $result, 'ok' => true], 30 * 86400);
        printf("[%s] %s: %s (%.0f ms)\n", date('c'), $name, $result, (microtime(true) - $t) * 1000);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        Cache::set("cron:last:$name", ['at' => date('c'), 'result' => $e->getMessage(), 'ok' => false], 30 * 86400);
        fwrite(STDERR, '[' . date('c') . "] $name FAILED: " . $e->getMessage() . "\n");
    } finally {
        Cache::forget("cron:lock:$name");
    }
}
