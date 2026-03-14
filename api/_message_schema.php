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

function ensure_group_message_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_groups (
            group_id BIGSERIAL PRIMARY KEY,
            mentor_user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
            title TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_group_members (
            group_id BIGINT NOT NULL REFERENCES mentorship_groups(group_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            member_role VARCHAR(20) NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP NOT NULL DEFAULT NOW(),
            PRIMARY KEY (group_id, user_id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_group_messages (
            message_id BIGSERIAL PRIMARY KEY,
            group_id BIGINT NOT NULL REFERENCES mentorship_groups(group_id) ON DELETE CASCADE,
            sender_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            content_file_path TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            edited_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_group_message_reads (
            group_id BIGINT NOT NULL REFERENCES mentorship_groups(group_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            last_read_message_id BIGINT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
            PRIMARY KEY (group_id, user_id)
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_group_members_user ON mentorship_group_members(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_group_messages_group ON mentorship_group_messages(group_id, created_at DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_group_reads_user ON mentorship_group_message_reads(user_id)");

    $done = true;
}
