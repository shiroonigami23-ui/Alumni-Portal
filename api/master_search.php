<?php
/**
 * Global search used by the Ctrl+K command palette.
 * GET ?q=term  →  { success, results: { users, jobs, events, referrals } }
 * Private profiles are hidden (admins see everyone). Max 5 per group.
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$viewer = (int)$auth->validateRequest();

$term = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
$results = ['users' => [], 'jobs' => [], 'events' => [], 'referrals' => []];
if (mb_strlen($term) < 2) {
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
$op = db_is_pgsql($db) ? 'ILIKE' : 'LIKE';

$isAdmin = api_user_role($db, (int)$viewer) === 'admin';

$run = function (string $sql, array $params) use ($db) {
    try {
        $s = $db->prepare($sql);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('master_search: ' . $e->getMessage());
        return [];
    }
};

$isPg = db_is_pgsql($db);
$results['users'] = $run("
    SELECT u.user_id, u.username, p.full_name, u.role,
           CASE WHEN lk.locked THEN NULL ELSE p.current_company END AS current_company,
           CASE WHEN lk.locked THEN NULL ELSE p.job_role END AS job_role,
           p.graduation_year,
           CASE WHEN lk.locked THEN NULL ELSE p.location_city END AS location_city,
           lk.locked AS is_private
    FROM users u JOIN profiles p ON p.user_id = u.user_id
    CROSS JOIN LATERAL (SELECT (:adm = 0 AND u.role <> 'student' AND COALESCE(p.is_private, FALSE) = TRUE AND u.user_id <> :me
        AND NOT EXISTS (SELECT 1 FROM connections c WHERE c.status = 'accepted' AND c.requester_user_id = :me AND c.addressee_user_id = u.user_id)) AS locked) lk
    WHERE u.status = 'active'
      AND (p.full_name $op :q1 OR lower(u.username) LIKE :qh OR (NOT lk.locked AND (p.current_company $op :q2 OR p.location_city $op :q3)))
      AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.blocker_user_id = :me AND b.blocked_user_id = u.user_id)
                                              OR (b.blocked_user_id = :me AND b.blocker_user_id = u.user_id))
    ORDER BY CASE WHEN p.full_name $op :q4 THEN 0 ELSE 1 END, p.full_name
    LIMIT 5", ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $term . '%', 'qh' => str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], ltrim(strtolower($term), '@')) . '%', 'me' => (int)$viewer, 'adm' => $isAdmin ? 1 : 0]);

if ($isPg) {
    // Jobs, events and referral offers in one round trip: each sub-select returns a JSON array.
    $row = $run("
        SELECT
            (SELECT COALESCE(json_agg(j), '[]'::json) FROM (
                SELECT job_id, company_name, job_title, location FROM jobs
                WHERE COALESCE(status, 'open') = 'open' AND (job_title ILIKE :q1 OR company_name ILIKE :q2)
                ORDER BY created_at DESC LIMIT 5) j) AS jobs,
            (SELECT COALESCE(json_agg(e), '[]'::json) FROM (
                SELECT event_id, title, start_datetime::text AS start_datetime FROM events
                WHERE title ILIKE :q3 AND status = 'approved'
                ORDER BY start_datetime DESC LIMIT 5) e) AS events,
            (SELECT COALESCE(json_agg(r), '[]'::json) FROM (
                SELECT offer_id, company, roles FROM referral_offers
                WHERE status = 'open' AND (company ILIKE :q4 OR roles ILIKE :q5)
                ORDER BY created_at DESC LIMIT 5) r) AS referrals",
        ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like])[0] ?? [];
    foreach (['jobs', 'events', 'referrals'] as $k) {
        $results[$k] = json_decode((string)($row[$k] ?? '[]'), true) ?: [];
    }
} else {
    $results['jobs'] = $run("
        SELECT job_id, company_name, job_title, location
        FROM jobs WHERE COALESCE(status, 'open') = 'open' AND (job_title $op :q1 OR company_name $op :q2)
        ORDER BY created_at DESC LIMIT 5", ['q1' => $like, 'q2' => $like]);
    $results['events'] = $run("
        SELECT event_id, title, start_datetime
        FROM events WHERE title $op :q1 AND status = 'approved'
        ORDER BY start_datetime DESC LIMIT 5", ['q1' => $like]);
    $results['referrals'] = $run("
        SELECT offer_id, company, roles FROM referral_offers
        WHERE status = 'open' AND (company $op :q1 OR roles $op :q2)
        ORDER BY created_at DESC LIMIT 5", ['q1' => $like, 'q2' => $like]);
}

echo json_encode(['success' => true, 'results' => $results]);
