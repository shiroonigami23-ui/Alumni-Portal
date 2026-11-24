<?php
if (PHP_SAPI !== 'cli') {   // command-line only — never runnable from a web request, even if a server serves this folder by mistake
    http_response_code(404);
    exit;
}
/**
 * Archive + purge logic shared by cron/run.php and the standalone scripts.
 *
 * Archiving moves a post out of the live feed into archived_posts. Because that
 * removes the post's comments, likes and reposts (database cascade), it is
 * deliberately conservative:
 *   - off unless ARCHIVE_ENABLED=true (or run by hand with --apply)
 *   - only posts older than ARCHIVE_AFTER_DAYS (default 365)
 *   - never pinned posts, never announcements, never posts with recent activity (30 days)
 *   - dry run by default: prints what would happen
 */
require_once __DIR__ . '/../api/_content_store.php';

function archive_candidates_sql(): string
{
    return "FROM posts p
            WHERE p.created_at <= NOW() - (CAST(:days AS INT) * INTERVAL '1 day')
              AND COALESCE(p.is_pinned, FALSE) = FALSE
              AND p.post_type <> 'announcement'
              AND NOT EXISTS (SELECT 1 FROM pinned_posts pp WHERE pp.post_id = p.post_id)
              AND NOT EXISTS (SELECT 1 FROM comments c WHERE c.post_id = p.post_id AND c.created_at > NOW() - INTERVAL '30 days')";
}

function archive_old_posts(PDO $db, int $days, bool $apply): int
{
    $days = max(30, $days);
    $count = $db->prepare("SELECT COUNT(*) " . archive_candidates_sql());
    $count->execute(['days' => $days]);
    $n = (int)$count->fetchColumn();
    if (!$apply || $n === 0) {
        return $n;
    }
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO archived_posts (original_post_id, user_id, post_type, content_file_path,
                        final_reaction_count, final_comment_count, final_view_count, original_created_at)
                      SELECT p.post_id, p.user_id, p.post_type, p.content_file_path, p.reaction_count, p.comment_count, p.view_count, p.created_at "
                      . archive_candidates_sql())->execute(['days' => $days]);
        $del = $db->prepare("DELETE FROM posts WHERE post_id IN (SELECT p.post_id " . archive_candidates_sql() . ")");
        $del->execute(['days' => $days]);
        $db->commit();
        return $del->rowCount();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/** Permanently delete the stored content of posts archived more than 30 days ago. */
function purge_archived_content(PDO $db, bool $apply): int
{
    $rows = $db->query("SELECT archive_id, content_file_path FROM archived_posts
                        WHERE archived_at <= NOW() - INTERVAL '30 days' AND content_deleted = FALSE LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
    if (!$apply) {
        return count($rows);
    }
    $done = 0;
    $mark = $db->prepare("UPDATE archived_posts SET content_deleted = TRUE, content_deleted_at = NOW() WHERE archive_id = :id");
    foreach ($rows as $r) {
        $path = (string)$r['content_file_path'];
        if (extract_content_token($path)) {
            delete_content_payload($db, $path);            // DB-backed content
        } else {
            $file = realpath(dirname(__DIR__) . '/' . $path);  // legacy text file
            $base = realpath(dirname(__DIR__) . '/storage');
            if ($file && $base && strpos($file, $base) === 0 && is_file($file)) {
                @unlink($file);
            }
        }
        $mark->execute(['id' => $r['archive_id']]);
        $done++;
    }
    return $done;
}
