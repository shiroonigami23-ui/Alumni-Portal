<?php
/**
 * One-command database installer (PostgreSQL).
 *
 *   php scripts/install_db.php               base schema + every feature table + migrations
 *   php scripts/install_db.php --demo        …plus a realistic demo dataset (local/testing only)
 *   php scripts/install_db.php --admin-password='S3cure!pass'   set the seeded admin password
 *
 * Connection comes from the usual DB_* environment variables (see .env.example).
 * Safe to re-run: everything is idempotent.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config/Database.php';
require_once $root . '/config/DbCompat.php';
foreach (glob($root . '/api/_*schema*.php') as $f) {
    require_once $f;
}
require_once $root . '/api/_asset_store.php';
require_once $root . '/api/_content_store.php';
require_once $root . '/api/_portal_settings.php';

$opts = getopt('', ['demo', 'admin-password:']);
$t0 = microtime(true);
$say = fn ($m) => print("  • $m\n");

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Could not connect. Check DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD.\n");
    exit(1);
}
if (!db_is_pgsql($db)) {
    fwrite(STDERR, "This installer targets PostgreSQL (the supported engine). Set DB_DRIVER=pgsql.\n");
    exit(1);
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\nRJIT Alumni Portal — database install\n\n";

// 1) Base schema
$db->exec(file_get_contents($root . '/deployment/schema_pg.sql'));
$say('Base schema (deployment/schema_pg.sql)');

// 2) Feature tables owned by runtime helpers
$helpers = [
    'ensure_community_schema', 'ensure_content_store_schema', 'ensure_asset_store_schema', 'ensure_feed_metrics_schema',
    'ensure_password_reset_schema', 'ensure_user_moderation_schema', 'ensureMentorshipSchema', 'ensure_message_columns',
    'ensure_calls_table', 'ensure_group_message_schema', 'ensure_group_call_schema', 'ensure_referral_schema', 'ensure_portal_settings',
];
foreach ($helpers as $fn) {
    if (function_exists($fn)) {
        $fn($db);
        $say("Feature tables: $fn");
    }
}

// 3) Historical PostgreSQL migrations (all idempotent)
foreach (glob($root . '/deployment/sql/*.sql') as $file) {
    $name = basename($file);
    if (stripos($name, 'mysql') !== false || preg_match('/seed|cleanup|fix_student_ids/i', $name)) {
        continue; // data-specific scripts are run manually on purpose
    }
    try {
        $db->exec(file_get_contents($file));
        $say("Migration $name");
    } catch (Throwable $e) {
        $say("Migration $name skipped: " . strtok($e->getMessage(), "\n"));
    }
}
$db->exec(file_get_contents($root . '/scripts/ensure_perf_indexes.sql'));
$say('Performance indexes');

// 4) Admin password
if (!empty($opts['admin-password'])) {
    $db->prepare("UPDATE users SET password_hash = :h WHERE email = 'admin@rjit.ac.in'")
       ->execute(['h' => password_hash($opts['admin-password'], PASSWORD_DEFAULT)]);
    $say('Admin password set');
}

// 5) Optional demo data
if (isset($opts['demo'])) {
    require __DIR__ . '/demo_data.php';
    $n = seed_demo_data($db);
    $say("Demo data: $n alumni with posts, jobs, events and referral offers");
}

$tables = (int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'")->fetchColumn();
printf("\nDone: %d tables ready in %.1fs.\n", $tables, microtime(true) - $t0);
if (empty($opts['admin-password'])) {
    echo "Seed admin: admin@rjit.ac.in / ChangeMe@123 — change it now (or re-run with --admin-password=…).\n";
}
