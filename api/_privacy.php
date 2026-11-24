<?php
/**
 * One place for "who may see what".
 *
 *  - Students are always public (portal policy).
 *  - A private account is visible to: itself, admins, and accepted followers.
 *    Everyone else sees a locked card (name, photo, role, branch, counts) and can send a follow request.
 *  - Blocking hides people from each other completely.
 *  - Email / phone are shown only when the owner turns them on (self and admins always see them).
 *  - Roll number is visible to the owner, admins and faculty only.
 */

if (!function_exists('privacy_ensure_schema')) {
    function privacy_ensure_schema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $run = function () use ($db) {
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS is_private BOOLEAN DEFAULT FALSE");
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS show_email BOOLEAN DEFAULT TRUE");
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS show_contact BOOLEAN DEFAULT FALSE");
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS open_to_mentor BOOLEAN DEFAULT FALSE");
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS open_to_refer BOOLEAN DEFAULT FALSE");
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS open_to_work BOOLEAN DEFAULT FALSE");
            $db->exec("ALTER TABLE profiles ADD COLUMN IF NOT EXISTS pronouns VARCHAR(40) NULL");
            $db->exec("ALTER TABLE connections ADD COLUMN IF NOT EXISTS accepted_at TIMESTAMP NULL");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_connections_pair ON connections (requester_user_id, addressee_user_id, status)");
            $db->exec("CREATE TABLE IF NOT EXISTS profile_views (
                viewer_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
                viewed_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
                view_day DATE NOT NULL DEFAULT CURRENT_DATE,
                viewed_at TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (viewer_user_id, viewed_user_id, view_day)
            )");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_profile_views_viewed ON profile_views (viewed_user_id, viewed_at DESC)");
            try {
                $db->exec("ALTER TYPE notification_type ADD VALUE IF NOT EXISTS 'follow_request'");
            } catch (Throwable $e) {
                // enum may not exist on very old schemas — notifications fall back to 'follow'
            }
        };
        if (!function_exists('schema_once')) {
            @include_once __DIR__ . '/../helpers/Cache.php';
        }
        if (function_exists('schema_once')) {
            schema_once('privacy_schema', $run, __FILE__);
        } else {
            $run();
        }
    }

    /** Accepts true/false, 1/0, "true"/"false", "on"/"off", "yes"/"no". Null when absent/unparseable. */
    function privacy_bool($value): ?bool
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    function privacy_role(PDO $db, int $uid): string
    {
        if ($uid <= 0) {
            return '';
        }
        $s = $db->prepare("SELECT role FROM users WHERE user_id = :u");
        $s->execute([':u' => $uid]);
        return strtolower((string)($s->fetchColumn() ?: ''));
    }

    function privacy_is_blocked(PDO $db, int $a, int $b): bool
    {
        if ($a <= 0 || $b <= 0 || $a === $b) {
            return false;
        }
        $s = $db->prepare("SELECT 1 FROM blocks WHERE (blocker_user_id = :a AND blocked_user_id = :b) OR (blocker_user_id = :b AND blocked_user_id = :a) LIMIT 1");
        $s->execute([':a' => $a, ':b' => $b]);
        return (bool)$s->fetchColumn();
    }

    /** 'self' | 'following' | 'requested' | 'none' */
    function privacy_follow_state(PDO $db, int $viewer, int $owner): string
    {
        if ($viewer > 0 && $viewer === $owner) {
            return 'self';
        }
        $s = $db->prepare("SELECT status FROM connections WHERE requester_user_id = :v AND addressee_user_id = :o LIMIT 1");
        $s->execute([':v' => $viewer, ':o' => $owner]);
        $st = (string)($s->fetchColumn() ?: '');
        return $st === 'accepted' ? 'following' : ($st === 'pending' ? 'requested' : 'none');
    }

    /** Is the owner's account effectively private? (students never are) */
    function privacy_is_private(PDO $db, int $owner): bool
    {
        $s = $db->prepare("SELECT u.role, COALESCE(p.is_private, FALSE) FROM users u LEFT JOIN profiles p ON p.user_id = u.user_id WHERE u.user_id = :u");
        $s->execute([':u' => $owner]);
        $row = $s->fetch(PDO::FETCH_NUM);
        if (!$row) {
            return false;
        }
        return strtolower((string)$row[0]) !== 'student' && filter_var($row[1], FILTER_VALIDATE_BOOLEAN);
    }

    function privacy_can_view(PDO $db, int $viewer, string $viewerRole, int $owner): bool
    {
        if ($viewer > 0 && $viewer === $owner) {
            return true;
        }
        if ($viewerRole === 'admin') {
            return true;
        }
        if (privacy_is_blocked($db, $viewer, $owner)) {
            return false;
        }
        if (!privacy_is_private($db, $owner)) {
            return true;
        }
        return privacy_follow_state($db, $viewer, $owner) === 'following';
    }

    /**
     * SQL condition: the author in $authorCol is visible to the viewer.
     * Uses named params :viewer_uid and :viewer_role (bind both).
     */
    function privacy_author_visible_sql(string $authorCol = 'p.user_id'): string
    {
        return "(
            {$authorCol} = :viewer_uid
            OR :viewer_role = 'admin'
            OR NOT EXISTS (
                SELECT 1 FROM profiles pv_p JOIN users pv_u ON pv_u.user_id = pv_p.user_id
                WHERE pv_p.user_id = {$authorCol} AND COALESCE(pv_p.is_private, FALSE) = TRUE AND pv_u.role <> 'student'
            )
            OR EXISTS (
                SELECT 1 FROM connections pv_c
                WHERE pv_c.status = 'accepted' AND pv_c.requester_user_id = :viewer_uid AND pv_c.addressee_user_id = {$authorCol}
            )
        )";
    }

    /** SQL condition: neither side has blocked the other. Uses :viewer_uid. */
    function privacy_not_blocked_sql(string $authorCol = 'p.user_id'): string
    {
        return "NOT EXISTS (SELECT 1 FROM blocks pv_b WHERE (pv_b.blocker_user_id = :viewer_uid AND pv_b.blocked_user_id = {$authorCol})
                                                      OR (pv_b.blocked_user_id = :viewer_uid AND pv_b.blocker_user_id = {$authorCol}))";
    }

    /** SQL condition: post audience (visibility_scope) allows the viewer's role. Uses :viewer_uid, :viewer_role. */
    function privacy_scope_sql(string $alias = 'p'): string
    {
        return "(
            {$alias}.user_id = :viewer_uid
            OR :viewer_role = 'admin'
            OR COALESCE({$alias}.visibility_scope, 'all') IN ('all', 'public', 'everyone')
            OR (COALESCE({$alias}.visibility_scope, 'all') = 'alumni' AND :viewer_role = 'alumni')
            OR (COALESCE({$alias}.visibility_scope, 'all') = 'faculty' AND :viewer_role = 'faculty')
            OR (COALESCE({$alias}.visibility_scope, 'all') = 'students' AND :viewer_role = 'student')
            OR (COALESCE({$alias}.visibility_scope, 'all') = 'faculty_alumni' AND :viewer_role IN ('faculty', 'alumni'))
            OR (COALESCE({$alias}.visibility_scope, 'all') = 'students_alumni' AND :viewer_role IN ('student', 'alumni'))
            OR (COALESCE({$alias}.visibility_scope, 'all') = 'faculty_students' AND :viewer_role IN ('faculty', 'student'))
        )";
    }

    /** Full "may this viewer see this post" SQL: published, approved (or own), audience, blocks, privacy. */
    function privacy_post_visible_sql(string $alias = 'p'): string
    {
        return "{$alias}.status = 'published'
            AND (COALESCE({$alias}.moderation_status, 'approved') = 'approved' OR {$alias}.user_id = :viewer_uid OR :viewer_role = 'admin')
            AND " . privacy_scope_sql($alias) . "
            AND " . privacy_not_blocked_sql("{$alias}.user_id") . "
            AND " . privacy_author_visible_sql("{$alias}.user_id");
    }

    function privacy_can_view_post(PDO $db, int $viewer, string $viewerRole, int $postId): bool
    {
        $s = $db->prepare("SELECT 1 FROM posts p WHERE p.post_id = :pid AND " . privacy_post_visible_sql('p'));
        $s->execute([':pid' => $postId, ':viewer_uid' => $viewer, ':viewer_role' => $viewerRole]);
        return (bool)$s->fetchColumn();
    }

    function privacy_notify(PDO $db, int $to, int $from, string $type, string $message): void
    {
        try {
            $s = $db->prepare("INSERT INTO notifications (user_id, notification_type, content, related_user_id) VALUES (:to, CAST(:t AS notification_type), :m, :from)");
            $s->execute([':to' => $to, ':t' => $type, ':m' => $message, ':from' => $from]);
        } catch (Throwable $e) {
            try {
                $s = $db->prepare("INSERT INTO notifications (user_id, notification_type, content, related_user_id) VALUES (:to, 'follow', :m, :from)");
                $s->execute([':to' => $to, ':m' => $message, ':from' => $from]);
            } catch (Throwable $e2) {
                // notifications are best-effort
            }
        }
    }

    function privacy_display_name(PDO $db, int $uid): string
    {
        $s = $db->prepare("SELECT COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) FROM users u LEFT JOIN profiles p ON p.user_id = u.user_id WHERE u.user_id = :u");
        $s->execute([':u' => $uid]);
        return (string)($s->fetchColumn() ?: 'Someone');
    }
}

if (!function_exists('privacy_require_post')) {
    /** Stop with 404 unless the viewer may see this post (same answer for "doesn't exist" and "not allowed"). */
    function privacy_require_post(PDO $db, int $viewer, int $postId): void
    {
        if ($postId > 0 && privacy_can_view_post($db, $viewer, privacy_role($db, $viewer), $postId)) {
            return;
        }
        http_response_code(404);
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'This post is no longer available.']);
        exit;
    }
}
