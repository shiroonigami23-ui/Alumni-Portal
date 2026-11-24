<?php

function db_driver(PDO $db): string
{
    static $driver = null;
    if ($driver !== null) {
        return $driver;
    }
    $driver = strtolower((string)$db->getAttribute(PDO::ATTR_DRIVER_NAME));
    return $driver;
}

function db_is_pgsql(PDO $db): bool
{
    return db_driver($db) === 'pgsql';
}

function db_is_mysql(PDO $db): bool
{
    return db_driver($db) === 'mysql';
}

function db_email_local_part_expr(PDO $db, string $column): string
{
    if (db_is_mysql($db)) {
        return "SUBSTRING_INDEX($column, '@', 1)";
    }
    return "split_part($column, '@', 1)";
}

/**
 * Schema lookups run on hot paths, so a positive answer is remembered (per request and in the shared cache
 * for an hour). Negative answers are never cached: a missing table/column may be created a moment later.
 */
function db_schema_memo(string $key, callable $check): bool
{
    static $local = [];
    if (isset($local[$key])) {
        return true;
    }
    if (!class_exists('Cache')) {
        @include_once __DIR__ . '/../helpers/Cache.php';
    }
    $ck = 'schema_has:' . $key;
    if (class_exists('Cache') && Cache::get($ck) === 1) {
        return $local[$key] = true;
    }
    if ($check()) {
        if (class_exists('Cache')) {
            Cache::set($ck, 1, 3600);
        }
        return $local[$key] = true;
    }
    return false;
}

function db_table_exists(PDO $db, string $table): bool
{
    return db_schema_memo('t:' . $table, fn () => db_table_exists_uncached($db, $table));
}

function db_column_exists(PDO $db, string $table, string $column): bool
{
    return db_schema_memo('c:' . $table . '.' . $column, fn () => db_column_exists_uncached($db, $table, $column));
}

function db_table_exists_uncached(PDO $db, string $table): bool
{
    $driver = db_driver($db);
    if ($driver === 'mysql') {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ");
    } else {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = 'public'
              AND table_name = :table
        ");
    }
    $stmt->execute(['table' => $table]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function db_column_exists_uncached(PDO $db, string $table, string $column): bool
{
    $driver = db_driver($db);
    if ($driver === 'mysql') {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table
              AND column_name = :column
        ");
    } else {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = :table
              AND column_name = :column
        ");
    }
    $stmt->execute([
        'table' => $table,
        'column' => $column,
    ]);
    return ((int)$stmt->fetchColumn()) > 0;
}
