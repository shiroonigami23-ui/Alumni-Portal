<?php

function ensure_message_columns(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS edited_at TIMESTAMP NULL");
    $db->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL");

    $done = true;
}

function ensure_calls_table(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS calls (
            call_id BIGSERIAL PRIMARY KEY,
            initiator_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            receiver_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            call_type VARCHAR(10) NOT NULL CHECK (call_type IN ('audio', 'video')),
            room_code VARCHAR(255) NOT NULL,
            room_url TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");

    $done = true;
}

