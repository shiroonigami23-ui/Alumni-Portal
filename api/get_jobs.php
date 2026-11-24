<?php
/**
 * Job board listing. GET ?all=1 includes closed jobs (for their posters/admins).
 * Poster names come from profiles — emails are never exposed.
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';
include_once '../models/Job.php';

$db = (new Database())->getConnection();
$viewer = 0;
if (!empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) || (function_exists('getallheaders') && !empty(array_change_key_case(getallheaders())['authorization']))) {
    $viewer = (int)(new Auth($db))->validateRequest();
}
$job = new Job($db);

try {
    $rows = $job->getAll($viewer, !empty($_GET['all']) && $viewer)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('get_jobs: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'count' => 0, 'records' => [], 'message' => 'Could not load jobs right now.']);
    exit;
}

$records = [];
foreach ($rows as $r) {
    $mine = $viewer && (int)$r['poster_id'] === $viewer;
    $records[] = [
        'job_id' => (int)$r['job_id'],
        'company' => $r['company_name'],
        'title' => $r['job_title'],
        'description' => $job->getDescription($r['description_file_path']),
        'location' => $r['location'],
        'salary' => $r['salary_range'],
        'apply_url' => $r['application_url'] ?: null,
        'application_url' => $r['application_url'] ?: null,
        'type' => $r['job_type'],
        'status' => $r['status'],
        'poster_id' => (int)$r['poster_id'],
        'posted_by' => $r['poster_name'],
        'poster_role' => $r['poster_role'],
        'posted_at' => $r['created_at'],
        'applicant_count' => $mine ? (int)$r['applicant_count'] : null,
        'applied' => (int)$r['applied'] > 0,
        'is_mine' => $mine,
    ];
}
echo json_encode(['success' => true, 'count' => count($records), 'records' => $records]);
