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

function db_table_exists(PDO $db, string $table): bool
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

function db_column_exists(PDO $db, string $table, string $column): bool
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
