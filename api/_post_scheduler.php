<?php
/**
 * Scheduled posts: storage + publishing engine.
 *
 * publish_due_scheduled_posts() is called by cron/run.php (every minute) AND
 * opportunistically by the feed (at most once a minute via the cache), so
 * scheduled posts go live even on servers where nobody configured cron.
 * Rows are claimed with FOR UPDATE SKIP LOCKED, so concurrent runners never
 * publish the same post twice.
 */
require_once __DIR__ . '/../config/DbCompat.php';
require_once __DIR__ . '/../helpers/Cache.php';
require_once __DIR__ . '/_content_store.php';
require_once __DIR__ . '/_community_schema.php';
require_once __DIR__ . '/_moderation_schema.php';
require_once __DIR__ . '/_polls.php';

function ensure_scheduled_posts_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $key = 'schema:scheduled_posts:' . @filemtime(__FILE__) . ':' . (getenv('DB_NAME') ?: 'db');
    if (Cache::get($key)) {
        $done = true;
        return;
    }
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_posts (
        scheduled_id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
        content_path TEXT NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'text',
        publish_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
    )");
    $db->exec("DO $$ BEGIN
        IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'scheduled_posts' AND column_name = 'schedule_id') THEN
            ALTER TABLE scheduled_posts RENAME COLUMN schedule_id TO scheduled_id;
        END IF;
    END $$");
    foreach ([
        "visibility_scope VARCHAR(32) NOT NULL DEFAULT 'all'",
        "comments_enabled BOOLEAN NOT NULL DEFAULT TRUE",
        "poll_json TEXT NULL",
        "status VARCHAR(20) NOT NULL DEFAULT 'pending'",
        "published_post_id BIGINT NULL",
        "last_error TEXT NULL",
    ] as $col) {
        $db->exec("ALTER TABLE scheduled_posts ADD COLUMN IF NOT EXISTS $col");
    }
    $db->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_posts_due ON scheduled_posts (status, publish_at)");
    Cache::set($key, 1, 86400);
    $done = true;
}

/** Load the poster's permissions in the shape the community helpers expect. */
function scheduler_user_perms(PDO $db, int $userId): array
{
    $s = $db->prepare("SELECT role, can_post, COALESCE(is_moderator, FALSE) AS is_moderator, status FROM users WHERE user_id = :u");
    $s->execute(['u' => $userId]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: [];
}

function scheduler_notify(PDO $db, int $userId, string $text, ?int $postId = null): void
{
    foreach (['new_post', 'general'] as $type) {
        try {
            $db->prepare("INSERT INTO notifications (user_id, notification_type, related_post_id, content) VALUES (:u, :t, :p, :c)")
               ->execute(['u' => $userId, 't' => $type, 'p' => $postId, 'c' => $text]);
            return;
        } catch (Throwable $e) {
        }
    }
}

/**
 * Publish every scheduled post whose time has come. Returns number published.
 */
function publish_due_scheduled_posts(PDO $db, int $limit = 50): int
{
    ensure_scheduled_posts_schema($db);
    ensure_community_schema($db);
    $published = 0;
    for ($i = 0; $i < $limit; $i++) {
        $db->beginTransaction();
        try {
            $row = $db->query("SELECT * FROM scheduled_posts
                               WHERE COALESCE(status, 'pending') = 'pending' AND publish_at <= NOW()
                               ORDER BY publish_at ASC LIMIT 1 FOR UPDATE SKIP LOCKED")->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $db->commit();
                break;
            }
            $sid = (int)$row['scheduled_id'];
            $uid = (int)$row['user_id'];
            $perms = scheduler_user_perms($db, $uid);
            $restrictions = moderation_get_restrictions($db, $uid);
            if (($perms['status'] ?? '') !== 'active' || !user_can_create_top_level_posts($perms) || $restrictions['posting_blocked']) {
                $db->prepare("UPDATE scheduled_posts SET status = 'failed', last_error = :e WHERE scheduled_id = :id")
                   ->execute(['e' => 'Your account could not publish posts at the scheduled time.', 'id' => $sid]);
                $db->commit();
                scheduler_notify($db, $uid, 'A scheduled post could not be published because posting is restricted on your account.');
                continue;
            }

            // Legacy rows stored text files; move them into the DB content store.
            $pointer = (string)$row['content_path'];
            if (!extract_content_token($pointer)) {
                $payload = load_content_payload($db, $pointer);
                $pointer = store_content_payload($db, $uid, ['content' => (string)($payload['content'] ?? ''), 'attachments' => $payload['attachments'] ?? []], 'post');
            }
            $payload = load_content_payload($db, $pointer);
            $moderation = post_needs_moderation($perms) ? 'pending' : 'approved';
            $ins = $db->prepare("INSERT INTO posts (user_id, title, content_file_path, post_type, status, moderation_status, visibility_scope, comments_enabled, created_at)
                                 VALUES (:u, :t, :p, :ty, 'published', :m, :v, :c, NOW()) RETURNING post_id");
            $ins->execute([
                'u' => $uid, 't' => mb_substr((string)($payload['content'] ?? ''), 0, 80) ?: 'Scheduled post', 'p' => $pointer,
                'ty' => $row['type'] ?: 'text', 'm' => $moderation, 'v' => $row['visibility_scope'] ?: 'all',
                'c' => filter_var($row['comments_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            ]);
            $postId = (int)$ins->fetchColumn();

            if (!empty($row['poll_json'])) {
                $poll = json_decode((string)$row['poll_json'], true);
                if (is_array($poll)) {
                    poll_create($db, $postId, (string)($poll['question'] ?? ''), (array)($poll['options'] ?? []), isset($poll['duration_hours']) ? (int)$poll['duration_hours'] : null);
                }
            }
            $db->prepare("UPDATE scheduled_posts SET status = 'published', published_post_id = :p, content_path = :c WHERE scheduled_id = :id")
               ->execute(['p' => $postId, 'c' => $pointer, 'id' => $sid]);
            $db->commit();
            scheduler_notify($db, $uid, $moderation === 'pending'
                ? 'Your scheduled post was published and is waiting for moderator review.'
                : 'Your scheduled post is now live on the feed. 🎉', $postId);
            $published++;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('scheduler: ' . $e->getMessage());
            if (isset($sid)) {
                try {
                    $db->prepare("UPDATE scheduled_posts SET status = 'failed', last_error = :e WHERE scheduled_id = :id")
                       ->execute(['e' => 'Publishing failed. Please reschedule.', 'id' => $sid]);
                } catch (Throwable $e2) {
                }
            }
        }
    }
    if ($published) {
        Cache::bump('public_feed');
    }
    return $published;
}

/** Cheap hook for hot endpoints: run the publisher at most once a minute. */
function maybe_publish_due_posts(PDO $db): void
{
    if (getenv('SCHEDULER_LAZY') === 'false' || Cache::get('scheduler:lazy_lock')) {
        return;
    }
    Cache::set('scheduler:lazy_lock', 1, 60);
    try {
        publish_due_scheduled_posts($db, 20);
    } catch (Throwable $e) {
        error_log('lazy scheduler: ' . $e->getMessage());
    }
}
