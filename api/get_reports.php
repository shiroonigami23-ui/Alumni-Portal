<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'admin']);   // only admins see reporter identities — enforced before anything below
require_once __DIR__ . '/_moderation_schema.php';
ensure_report_review_schema($api->db);
header("Content-Type: application/json; charset=UTF-8");

// One round trip: the newest 500 reports (open ones first) with post, author and
// reporter details, plus each reporter's track record. The rejected-report tally is
// aggregated once per reporter in a CTE instead of a correlated sub-query per row.
$query = "
    WITH page AS (
        SELECT r.*
        FROM reports r
        ORDER BY (r.status IN ('pending', 'reviewed')) DESC, r.created_at DESC
        LIMIT 500
    ), history AS (
        SELECT rr.reporter_user_id, COUNT(*) FILTER (WHERE rr.status = 'rejected') AS rejected
        FROM reports rr
        WHERE rr.reporter_user_id IN (SELECT DISTINCT reporter_user_id FROM page)
        GROUP BY rr.reporter_user_id
    )
    SELECT
        r.report_id,
        r.reported_post_id AS post_id,
        p.title AS post_title,
        p.user_id AS author_id,
        owner.email AS author_email,
        owner.role AS author_role,
        r.reason,
        r.custom_reason,
        r.status,
        r.created_at,
        u.email AS reporter_email,
        r.reporter_user_id AS reporter_id,
        COALESCE(p.report_count, 0) AS report_count,
        p.status::text AS post_status,
        COALESCE(p.is_restored, FALSE) AS post_restored,
        u.role AS reporter_role,
        COALESCE(ms.warning_count, 0) AS reporter_warnings,
        COALESCE(ms.strike_count, 0) AS reporter_strikes,
        COALESCE(h.rejected, 0) AS reporter_rejected_reports
    FROM page r
    JOIN posts p ON r.reported_post_id = p.post_id
    JOIN users u ON r.reporter_user_id = u.user_id
    JOIN users owner ON owner.user_id = p.user_id
    LEFT JOIN moderation_strikes ms ON ms.user_id = r.reporter_user_id
    LEFT JOIN history h ON h.reporter_user_id = r.reporter_user_id
    ORDER BY (r.status IN ('pending', 'reviewed')) DESC, r.created_at DESC";

try {
    $reports = $api->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reports as &$row) {
        $row['post_restored'] = (bool)$row['post_restored'];
        foreach (['report_id', 'post_id', 'author_id', 'reporter_id', 'report_count', 'reporter_warnings', 'reporter_strikes', 'reporter_rejected_reports'] as $k) {
            $row[$k] = (int)$row[$k];
        }
    }
    unset($row);
    echo json_encode($reports);
} catch (Throwable $e) {
    api_fail(500, 'Could not load reports.', ['error_ref' => api_error_ref($e)]);
}
