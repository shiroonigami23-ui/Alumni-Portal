<?php
require_once __DIR__ . '/../config/DbCompat.php';

function ensure_community_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    if (!db_column_exists($db, 'users', 'is_moderator')) {
        $db->exec("ALTER TABLE users ADD COLUMN is_moderator BOOLEAN NOT NULL DEFAULT FALSE");
    }

    if (!db_column_exists($db, 'posts', 'visibility_scope')) {
        $db->exec("ALTER TABLE posts ADD COLUMN visibility_scope VARCHAR(32) NOT NULL DEFAULT 'all'");
    }

    if (!db_column_exists($db, 'posts', 'moderation_status')) {
        $db->exec("ALTER TABLE posts ADD COLUMN moderation_status VARCHAR(32) NOT NULL DEFAULT 'approved'");
    }

    if (!db_column_exists($db, 'posts', 'reviewed_by_user_id')) {
        $db->exec("ALTER TABLE posts ADD COLUMN reviewed_by_user_id BIGINT NULL");
    }

    if (!db_column_exists($db, 'posts', 'reviewed_at')) {
        $db->exec("ALTER TABLE posts ADD COLUMN reviewed_at TIMESTAMP NULL");
    }

    if (!db_column_exists($db, 'posts', 'review_note')) {
        $db->exec("ALTER TABLE posts ADD COLUMN review_note TEXT NULL");
    }

    if (!db_column_exists($db, 'posts', 'revision_no')) {
        $db->exec("ALTER TABLE posts ADD COLUMN revision_no INTEGER NOT NULL DEFAULT 1");
    }

    if (!db_column_exists($db, 'posts', 'pending_revision_no')) {
        $db->exec("ALTER TABLE posts ADD COLUMN pending_revision_no INTEGER NULL");
    }

    if (!db_column_exists($db, 'posts', 'pending_edit_status')) {
        $db->exec("ALTER TABLE posts ADD COLUMN pending_edit_status VARCHAR(32) NOT NULL DEFAULT 'none'");
    }

    if (!db_column_exists($db, 'posts', 'pending_edit_content_file_path')) {
        $db->exec("ALTER TABLE posts ADD COLUMN pending_edit_content_file_path TEXT NULL");
    }

    if (!db_column_exists($db, 'posts', 'pending_edit_submitted_at')) {
        $db->exec("ALTER TABLE posts ADD COLUMN pending_edit_submitted_at TIMESTAMP NULL");
    }

    if (!db_column_exists($db, 'posts', 'previous_content_file_path')) {
        $db->exec("ALTER TABLE posts ADD COLUMN previous_content_file_path TEXT NULL");
    }

    if (!db_column_exists($db, 'posts', 'previous_revision_no')) {
        $db->exec("ALTER TABLE posts ADD COLUMN previous_revision_no INTEGER NULL");
    }

    try {
        if (db_is_mysql($db)) {
            $db->exec("CREATE INDEX idx_posts_moderation_status ON posts(moderation_status)");
            $db->exec("CREATE INDEX idx_posts_visibility_scope ON posts(visibility_scope)");
            $db->exec("CREATE INDEX idx_posts_pending_edit_status ON posts(pending_edit_status)");
            $db->exec("CREATE INDEX idx_users_is_moderator ON users(is_moderator)");
        } else {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_posts_moderation_status ON posts(moderation_status)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_posts_visibility_scope ON posts(visibility_scope)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_posts_pending_edit_status ON posts(pending_edit_status)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_users_is_moderator ON users(is_moderator)");
        }
    } catch (Throwable $ignored) {
        // Index may already exist in older environments.
    }

    // Keep legacy posts visible.
    $db->exec("
        UPDATE posts
        SET moderation_status = 'approved'
        WHERE moderation_status IS NULL OR moderation_status = ''
    ");

    $db->exec("
        UPDATE posts
        SET pending_edit_status = 'none'
        WHERE pending_edit_status IS NULL OR pending_edit_status = ''
    ");

    $db->exec("
        UPDATE posts
        SET revision_no = 1
        WHERE revision_no IS NULL OR revision_no < 1
    ");

    $done = true;
}

function user_can_moderate_posts(array $user): bool
{
    $role = strtolower((string)($user['role'] ?? ''));
    $isModerator = !empty($user['is_moderator']);
    return $role === 'admin' || $isModerator;
}

function user_can_create_top_level_posts(array $user): bool
{
    $role = strtolower((string)($user['role'] ?? ''));
    $isModerator = !empty($user['is_moderator']);
    $canPost = array_key_exists('can_post', $user) ? (bool)$user['can_post'] : true;

    if (!$canPost) {
        return false;
    }
    if ($role === 'student' && !$isModerator) {
        return false;
    }
    return in_array($role, ['admin', 'faculty', 'alumni', 'student'], true);
}

function post_needs_moderation(array $user): bool
{
    $role = strtolower((string)($user['role'] ?? ''));
    $isModerator = !empty($user['is_moderator']);
    return $role === 'alumni' && !$isModerator;
}
