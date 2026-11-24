<?php
/**
 * Referral board schema (PostgreSQL + MySQL). Created on first use.
 * Internal include — never web-reachable (files starting with "_" are blocked by the router).
 */
require_once __DIR__ . '/../config/DbCompat.php';

function ensure_referral_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    require_once __DIR__ . '/../helpers/Cache.php';
    $__schemaKey = 'schema:ensure_referral_schema:' . @filemtime(__FILE__) . ':' . (getenv('DB_NAME') ?: 'db');
    if (Cache::get($__schemaKey)) {
        $done = true;
        return;
    }
    $pg = db_is_pgsql($db);
    $id = $pg ? 'BIGSERIAL PRIMARY KEY' : 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    $fk = $pg ? 'BIGINT' : 'BIGINT UNSIGNED';
    $now = $pg ? 'NOW()' : 'CURRENT_TIMESTAMP';

    $db->exec("
        CREATE TABLE IF NOT EXISTS referral_offers (
            offer_id $id,
            owner_id $fk NOT NULL,
            company VARCHAR(160) NOT NULL,
            roles VARCHAR(400) NOT NULL,
            locations VARCHAR(255) NULL,
            experience VARCHAR(20) NOT NULL DEFAULT 'any',
            slots INT NOT NULL DEFAULT 3,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            expires_at DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT $now,
            updated_at TIMESTAMP NOT NULL DEFAULT $now,
            FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS referral_requests (
            request_id $id,
            offer_id $fk NOT NULL,
            requester_id $fk NOT NULL,
            message TEXT NOT NULL,
            resume_url VARCHAR(500) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            owner_note VARCHAR(500) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT $now,
            updated_at TIMESTAMP NOT NULL DEFAULT $now,
            UNIQUE (offer_id, requester_id),
            FOREIGN KEY (offer_id) REFERENCES referral_offers(offer_id) ON DELETE CASCADE,
            FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    try {
        $db->exec("CREATE INDEX " . ($pg ? 'IF NOT EXISTS ' : '') . "idx_referral_offers_status ON referral_offers (status, created_at)");
    } catch (Throwable $e) {
        // MySQL has no IF NOT EXISTS for indexes; already exists is fine.
    }
    try {
        $db->exec("CREATE INDEX " . ($pg ? 'IF NOT EXISTS ' : '') . "idx_referral_requests_requester ON referral_requests (requester_id, status)");
    } catch (Throwable $e) {
    }
    Cache::set($__schemaKey, 1, 86400);
    $done = true;
}
