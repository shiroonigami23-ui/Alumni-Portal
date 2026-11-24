<?php
/**
 * Admin decisions on reports (Admin → Reports).
 *
 * POST { action, ... }
 *   dismiss              { report_id }   report was wrong but honest — close it
 *   resolve              { report_id }   handled (e.g. after deleting the post or banning the author)
 *   resolve_post_reports { post_id }     close every report on a post
 *   restore_post         { post_id }     bring back a post that reports hid automatically; its open reports are
 *                                        dismissed and it can't be auto-hidden again (admins can still remove it)
 *   penalize_reporter    { report_id }   the report was false/malicious: reject it and warn the reporter —
 *                                        3 warnings = a strike and a posting/messaging pause of 24 h × strikes
 */
require_once __DIR__ . '/_kernel.php';
require_once __DIR__ . '/_moderation_schema.php';
require_once __DIR__ . '/_privacy.php';
$api = api_endpoint(['access' => 'admin', 'methods' => ['POST']]);   // who may call this — enforced before anything below

$db = $api->db;
ensure_report_review_schema($db);
$action = $api->action;

$report = null;
if (in_array($action, ['dismiss', 'resolve', 'penalize_reporter'], true)) {
    $reportId = $api->int('report_id', true);
    $st = $db->prepare("SELECT report_id, reported_post_id, reporter_user_id, status::text AS status FROM reports WHERE report_id = :r");
    $st->execute([':r' => $reportId]);
    $report = $st->fetch(PDO::FETCH_ASSOC);
    if (!$report) {
        api_fail(404, 'Report not found.');
    }
}

switch ($action) {
    case 'dismiss':
        $db->prepare("UPDATE reports SET status = 'rejected'::report_status WHERE report_id = :r")->execute([':r' => $report['report_id']]);
        $api->log('DISMISS_REPORT', "Dismissed report #{$report['report_id']}");
        api_ok(null, 'Report dismissed.');

    case 'resolve':
        $db->prepare("UPDATE reports SET status = 'resolved'::report_status WHERE report_id = :r")->execute([':r' => $report['report_id']]);
        $api->log('RESOLVE_REPORT', "Resolved report #{$report['report_id']}");
        api_ok(null, 'Report resolved.');

    case 'resolve_post_reports':
        $postId = $api->int('post_id', true);
        $db->prepare("UPDATE reports SET status = 'resolved'::report_status WHERE reported_post_id = :p AND status IN ('pending', 'reviewed')")
           ->execute([':p' => $postId]);
        $api->log('RESOLVE_POST_REPORTS', "Resolved reports for post #$postId");
        api_ok(null, 'Reports for this post marked resolved.');

    case 'restore_post':
        $postId = $api->int('post_id', true);
        $p = $db->prepare("SELECT post_id, user_id, status::text AS status FROM posts WHERE post_id = :p");
        $p->execute([':p' => $postId]);
        $post = $p->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            api_fail(404, 'Post not found.');
        }
        if (!in_array($post['status'], ['shadow_banned', 'hidden'], true)) {
            api_fail(409, 'This post isn\'t hidden, so there\'s nothing to restore.');
        }
        $db->beginTransaction();
        $db->prepare("UPDATE posts SET status = 'published'::post_status, report_count = 0, is_restored = TRUE,
                             restored_at = NOW(), restored_by = :a WHERE post_id = :p")
           ->execute([':p' => $postId, ':a' => $api->uid]);
        $closed = $db->prepare("UPDATE reports SET status = 'rejected'::report_status WHERE reported_post_id = :p AND status IN ('pending', 'reviewed')");
        $closed->execute([':p' => $postId]);
        $db->commit();
        privacy_notify($db, (int)$post['user_id'], $api->uid, 'moderation', 'An admin reviewed your post and restored it. Thanks for your patience.');
        $api->log('RESTORE_POST', "Restored post #$postId; dismissed {$closed->rowCount()} report(s)");
        api_ok(['post_id' => $postId, 'reports_dismissed' => $closed->rowCount()],
            'Post restored. Its reports were dismissed, and reports can no longer hide it automatically.');

    case 'penalize_reporter':
        if (!in_array($report['status'], ['pending', 'reviewed'], true)) {
            api_fail(409, 'This report was already closed, so the reporter can\'t be penalised for it again.');
        }
        $reporterId = (int)$report['reporter_user_id'];
        if (in_array(privacy_role($db, $reporterId), ['admin', 'faculty'], true)) {
            api_fail(403, 'Faculty and admin reports can\'t be penalised here.');
        }
        $db->beginTransaction();
        $db->prepare("UPDATE reports SET status = 'rejected'::report_status WHERE report_id = :r")->execute([':r' => $report['report_id']]);
        $db->prepare("INSERT INTO moderation_strikes (user_id, warning_count, strike_count, updated_at) VALUES (:u, 1, 0, NOW())
                      ON CONFLICT (user_id) DO UPDATE SET warning_count = moderation_strikes.warning_count + 1, updated_at = NOW()")
           ->execute([':u' => $reporterId]);
        $s = $db->prepare("SELECT warning_count, strike_count FROM moderation_strikes WHERE user_id = :u");
        $s->execute([':u' => $reporterId]);
        [$warnings, $strikes] = array_map('intval', $s->fetch(PDO::FETCH_NUM));
        $until = null;
        if ($warnings >= 3) {
            $strikes++;
            $hours = 24 * $strikes;
            $db->prepare("UPDATE moderation_strikes SET strike_count = :s, warning_count = 0,
                                 shadow_ban_until = NOW() + (CAST(:h AS INTEGER) * INTERVAL '1 hour') WHERE user_id = :u")
               ->execute([':s' => $strikes, ':h' => $hours, ':u' => $reporterId]);
            $warnings = 0;
            $until = $hours;
        }
        $db->commit();
        privacy_notify($db, $reporterId, $api->uid, 'warning', $until
            ? "Several of your reports were found to be false. Posting and messaging are paused for $until hours."
            : "An admin found one of your reports to be false. Please only report content that breaks the community rules ($warnings of 3 warnings).");
        $api->log('PENALIZE_REPORTER', "False report #{$report['report_id']} by user #$reporterId: " . ($until ? "strike $strikes, paused {$until}h" : "warning $warnings/3"));
        api_ok(['reporter_id' => $reporterId, 'warnings' => $warnings, 'strikes' => $strikes, 'paused_hours' => $until],
            $until ? "Reporter reached 3 warnings: strike $strikes, posting paused for $until hours." : "Reporter warned ($warnings of 3).");
}

api_fail(400, 'Unknown action.');
