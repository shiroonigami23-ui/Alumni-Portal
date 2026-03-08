<?php

function ensure_feed_metrics_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS view_count INTEGER NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS share_count INTEGER NOT NULL DEFAULT 0");

    $db->exec("
        CREATE TABLE IF NOT EXISTS post_views (
            view_id BIGSERIAL PRIMARY KEY,
            post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            viewed_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (post_id, user_id)
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_post_views_post ON post_views(post_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_post_views_user ON post_views(user_id)");

    $db->exec("
        CREATE TABLE IF NOT EXISTS post_share_links (
            share_link_id BIGSERIAL PRIMARY KEY,
            post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
            sharer_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            token VARCHAR(128) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (post_id, sharer_user_id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS post_share_opens (
            share_open_id BIGSERIAL PRIMARY KEY,
            post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
            viewer_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            share_link_id BIGINT NULL REFERENCES post_share_links(share_link_id) ON DELETE SET NULL,
            opened_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (post_id, viewer_user_id)
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_post_share_links_post ON post_share_links(post_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_post_share_links_sharer ON post_share_links(sharer_user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_post_share_opens_post ON post_share_opens(post_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_post_share_opens_viewer ON post_share_opens(viewer_user_id)");

    $db->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS repost_count INTEGER NOT NULL DEFAULT 0");
    $db->exec("
        CREATE TABLE IF NOT EXISTS reposts (
            repost_id BIGSERIAL PRIMARY KEY,
            post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (post_id, user_id)
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_reposts_post ON reposts(post_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_reposts_user ON reposts(user_id)");

    $done = true;
}
