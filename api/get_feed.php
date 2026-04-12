<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../config/DbCompat.php';
include_once __DIR__ . '/_feed_schema.php';
include_once __DIR__ . '/_content_store.php';
include_once __DIR__ . '/_profile_media.php';
include_once __DIR__ . '/_community_schema.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Database unavailable.'
    ]);
    exit;
}
ensure_feed_metrics_schema($db);
ensure_community_schema($db);
$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();
    $viewerStmt = $db->prepare("SELECT role, COALESCE(is_moderator, FALSE) AS is_moderator FROM users WHERE user_id = :uid LIMIT 1");
    $viewerStmt->execute(['uid' => $user_id]);
    $viewer = $viewerStmt->fetch(PDO::FETCH_ASSOC) ?: ['role' => '', 'is_moderator' => false];
    $viewerRole = strtolower((string)($viewer['role'] ?? ''));
    $viewerIsAdmin = $viewerRole === 'admin';
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Unauthorized request.'
    ]);
    exit;
}

try {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $filter = strtolower(trim((string)($_GET['filter'] ?? 'all')));
    $sort = strtolower(trim((string)($_GET['sort'] ?? 'newest')));
    $authorUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $profileMode = $authorUserId > 0;
    $activityUserId = $authorUserId > 0 ? $authorUserId : $user_id;

    $where = "WHERE p.status = 'published'";
    $params = [
        'viewer_role' => $viewerRole,
        'viewer_uid' => $user_id,
    ];

    if ($authorUserId > 0 && $filter !== 'reposts') {
        $where .= " AND p.user_id = :author_uid";
        $params['author_uid'] = $authorUserId;
    }

    $connectionsTableExists = db_table_exists($db, 'connections');

    if ($filter === 'announcements') {
        $where .= " AND p.post_type = 'announcement'";
    } elseif ($filter === 'reposts') {
        if ($authorUserId > 0) {
            $where .= " AND EXISTS (SELECT 1 FROM reposts rp WHERE rp.post_id = p.post_id AND rp.user_id = :author_uid)";
            $params['author_uid'] = $authorUserId;
        } else {
            $where .= " AND EXISTS (SELECT 1 FROM reposts rp WHERE rp.post_id = p.post_id AND rp.user_id = :uid)";
            $params['uid'] = $user_id;
        }
    } elseif ($filter === 'following') {
        if ($connectionsTableExists) {
            $where .= " AND EXISTS (
                SELECT 1
                FROM connections c
                WHERE c.status = 'accepted'
                  AND (
                    (c.requester_user_id = :uid AND c.addressee_user_id = p.user_id)
                    OR
                    (c.addressee_user_id = :uid AND c.requester_user_id = p.user_id)
                  )
            )";
            $params['uid'] = $user_id;
        } else {
            $where .= " AND 1 = 0";
        }
    }

    if (!$viewerIsAdmin) {
        $where .= " AND (COALESCE(p.moderation_status, 'approved') = 'approved' OR p.user_id = :viewer_uid)";
    }

    $where .= " AND (
        p.user_id = :viewer_uid
        OR :viewer_role = 'admin'
        OR COALESCE(p.visibility_scope, 'all') = 'all'
        OR (COALESCE(p.visibility_scope, 'all') = 'alumni' AND :viewer_role = 'alumni')
        OR (COALESCE(p.visibility_scope, 'all') = 'faculty' AND :viewer_role = 'faculty')
        OR (COALESCE(p.visibility_scope, 'all') = 'students' AND :viewer_role = 'student')
        OR (COALESCE(p.visibility_scope, 'all') = 'faculty_alumni' AND :viewer_role IN ('faculty', 'alumni'))
        OR (COALESCE(p.visibility_scope, 'all') = 'students_alumni' AND :viewer_role IN ('student', 'alumni'))
        OR (COALESCE(p.visibility_scope, 'all') = 'faculty_students' AND :viewer_role IN ('faculty', 'student'))
    )";

    $orderBy = "ORDER BY p.created_at DESC";
    if ($filter === 'reposts') {
        $orderBy = "ORDER BY (activity_rp.created_at IS NULL), activity_rp.created_at DESC, p.created_at DESC";
    } elseif ($profileMode) {
        $orderBy = "ORDER BY CASE WHEN pp.post_id IS NULL THEN 1 ELSE 0 END, pp.pin_order ASC, p.created_at DESC";
    } elseif ($sort === 'popular') {
        $orderBy = "ORDER BY p.reaction_count DESC, p.comment_count DESC, p.created_at DESC";
    } elseif ($sort === 'oldest') {
        $orderBy = "ORDER BY p.created_at ASC";
    }

    $countSql = "SELECT COUNT(*) FROM posts p $where";
    $countStmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue(':' . $key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $isPinnedExpr = $profileMode
        ? "(pp.post_id IS NOT NULL)"
        : "(pp_self.post_id IS NOT NULL AND p.user_id = :viewer_id)";

    $sql = "SELECT 
                p.post_id AS id,
                p.user_id,
                p.title,
                p.post_type,
                p.content_file_path,
                p.thumbnail_url,
                p.reaction_count AS likes_count,
                p.comment_count AS comments_count,
                $isPinnedExpr AS is_pinned,
                p.comments_enabled AS allow_comments,
                COALESCE(p.share_count, 0) AS shares_count,
                COALESCE(p.view_count, 0) AS view_count,
                COALESCE(p.repost_count, 0) AS reposts_count,
                COALESCE(p.visibility_scope, 'all') AS visibility_scope,
                COALESCE(p.moderation_status, 'approved') AS moderation_status,
                COALESCE(p.pending_edit_status, 'none') AS pending_edit_status,
                p.pending_edit_submitted_at,
                COALESCE(p.revision_no, 1) AS revision_no,
                p.pending_revision_no,
                p.previous_content_file_path,
                p.previous_revision_no,
                p.pending_edit_content_file_path,
                activity_rp.created_at AS activity_reposted_at,
                (activity_rp.post_id IS NOT NULL) AS activity_user_has_reposted,
                p.created_at,
                u.role AS author_role,
                u.email AS author_email,
                pr.full_name AS author_name,
                pr.profile_picture_url AS author_avatar,
                pr.branch,
                EXISTS(
                    SELECT 1
                    FROM likes l
                    WHERE l.post_id = p.post_id
                      AND l.user_id = :viewer_id
                ) AS user_has_liked,
                EXISTS(
                    SELECT 1
                    FROM reposts rp
                    WHERE rp.post_id = p.post_id
                      AND rp.user_id = :viewer_id
                ) AS user_has_reposted
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN profiles pr ON p.user_id = pr.user_id
            LEFT JOIN reposts activity_rp ON activity_rp.post_id = p.post_id AND activity_rp.user_id = :activity_uid
            LEFT JOIN pinned_posts pp ON pp.post_id = p.post_id AND pp.user_id = :pin_owner
            LEFT JOIN pinned_posts pp_self ON pp_self.post_id = p.post_id AND pp_self.user_id = :viewer_id
            $where
            $orderBy
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':viewer_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':pin_owner', $profileMode ? $authorUserId : 0, PDO::PARAM_INT);
    $stmt->bindValue(':activity_uid', $activityUserId, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $posts = array_map(function (array $row) use ($db, $user_id) {
        $row['author_name'] = $row['author_name'] ?: 'RJIT Member';
        $avatar = (string)($row['author_avatar'] ?? '');
        if ($avatar !== '') {
            $avatar = str_replace('\\', '/', $avatar);
        }
        $looksPlaceholder = (
            $avatar === '' ||
            stripos($avatar, 'via.placeholder.com') !== false ||
            stripos($avatar, 'placeholder') !== false ||
            stripos($avatar, 'data:image/svg+xml') === 0
        );
        if ($looksPlaceholder && ($row['author_role'] ?? '') === 'faculty') {
            $emailSlug = strtolower((string)($row['author_email'] ?? ''));
            $emailSlug = preg_replace('/[^a-z0-9]+/', '_', $emailSlug);
            $candidates = [
                "storage/profiles/faculty_{$emailSlug}.jpg",
                "storage/profiles/faculty_{$emailSlug}.jpeg",
                "storage/profiles/faculty_{$emailSlug}.png",
                "storage/profiles/faculty_{$emailSlug}.JPG",
            ];
            foreach ($candidates as $candidate) {
                $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
                if (file_exists($abs)) {
                    $avatar = $candidate;
                    break;
                }
            }
        }
        $row['author_avatar'] = resolve_profile_media_url($db, (int)$row['user_id'], $avatar, 'profile_picture_url', 'profile_avatar');

        if (!empty($row['content_file_path'])) {
            $row['content_file_path'] = str_replace('\\', '/', (string)$row['content_file_path']);
        }
        $payload = load_content_payload($db, (string)$row['content_file_path']);
        $row['attachments'] = $payload['attachments'];
        $row['content'] = $payload['content'];
        if (trim((string)$row['content']) === '' && !empty($row['title'])) {
            $row['content'] = (string)$row['title'];
        }

        $pendingEditStatus = (string)($row['pending_edit_status'] ?? 'none');
        $row['has_pending_edit_review'] = ($pendingEditStatus === 'pending');
        $row['pending_edit_preview'] = null;
        if ($row['has_pending_edit_review'] && ((int)$row['user_id'] === (int)$user_id) && !empty($row['pending_edit_content_file_path'])) {
            $pendingPayload = load_content_payload($db, (string)$row['pending_edit_content_file_path']);
            $row['pending_edit_preview'] = [
                'content' => (string)($pendingPayload['content'] ?? ''),
                'attachments' => $pendingPayload['attachments'] ?? [],
                'pending_revision_no' => isset($row['pending_revision_no']) ? (int)$row['pending_revision_no'] : null,
                'submitted_at' => $row['pending_edit_submitted_at'] ?? null,
            ];
        }

        $row['previous_version_preview'] = null;
        if (!empty($row['previous_content_file_path']) && isset($row['previous_revision_no']) && (int)$row['previous_revision_no'] > 0) {
            $prevPayload = load_content_payload($db, (string)$row['previous_content_file_path']);
            $row['previous_version_preview'] = [
                'content' => (string)($prevPayload['content'] ?? ''),
                'attachments' => $prevPayload['attachments'] ?? [],
                'revision_no' => (int)$row['previous_revision_no'],
                'newer_revision_no' => isset($row['revision_no']) ? (int)$row['revision_no'] : null,
            ];
        }

        $row['is_owner'] = ((int)$row['user_id'] === (int)$user_id);
        unset($row['author_email']);
        unset($row['pending_edit_content_file_path'], $row['previous_content_file_path']);
        return $row;
    }, $rows);

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'data' => array_values($posts),
        'total' => $total,
        'page' => $page
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Failed to load feed.',
        'detail' => $e->getMessage()
    ]);
}
