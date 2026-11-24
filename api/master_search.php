<?php
/**
 * Global search used by the Ctrl+K command palette.
 * GET ?q=term  →  { success, results: { users, jobs, events, referrals } }
 * Private profiles are hidden (admins see everyone). Max 5 per group.
 */
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

$isAdmin = false;
$r = $db->prepare("SELECT role FROM users WHERE user_id = :u");
$r->execute(['u' => $viewer]);
$isAdmin = $r->fetchColumn() === 'admin';

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

$results['users'] = $run("
    SELECT u.user_id, p.full_name, u.role, p.current_company, p.job_role, p.graduation_year, p.location_city
    FROM users u JOIN profiles p ON p.user_id = u.user_id
    WHERE u.status = 'active'
      AND (p.full_name $op :q1 OR p.current_company $op :q2 OR p.location_city $op :q3)
      " . ($isAdmin ? '' : "AND COALESCE(p.is_private, " . (db_is_pgsql($db) ? 'false' : '0') . ") = " . (db_is_pgsql($db) ? 'false' : '0')) . "
    ORDER BY CASE WHEN p.full_name $op :q4 THEN 0 ELSE 1 END, p.full_name
    LIMIT 5", ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $term . '%']);

$results['jobs'] = $run("
    SELECT job_id, company_name, job_title, location
    FROM jobs WHERE (job_title $op :q1 OR company_name $op :q2)
    ORDER BY created_at DESC LIMIT 5", ['q1' => $like, 'q2' => $like]);

$results['events'] = $run("
    SELECT event_id, title, start_datetime
    FROM events WHERE title $op :q1 AND status = 'approved'
    ORDER BY start_datetime DESC LIMIT 5", ['q1' => $like]);

$results['referrals'] = $run("
    SELECT offer_id, company, roles FROM referral_offers
    WHERE status = 'open' AND (company $op :q1 OR roles $op :q2)
    ORDER BY created_at DESC LIMIT 5", ['q1' => $like, 'q2' => $like]);

echo json_encode(['success' => true, 'results' => $results]);
