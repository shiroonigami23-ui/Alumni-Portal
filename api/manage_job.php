<?php
/**
 * Poster / admin job management.
 * POST {job_id, action: update|close|reopen|delete, ...fields for update}
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';
include_once '../models/Job.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};

$data = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
$jobId = (int)($data['job_id'] ?? 0);
$action = (string)($data['action'] ?? '');

$s = $db->prepare("SELECT j.poster_id, j.description_file_path, (SELECT role FROM users WHERE user_id = :u) AS viewer_role FROM jobs j WHERE j.job_id = :id");
$s->execute(['id' => $jobId, 'u' => $userId]);
$job = $s->fetch(PDO::FETCH_ASSOC);
if (!$job) {
    $out(['success' => false, 'message' => 'That job no longer exists.'], 404);
}
$isAdmin = $job['viewer_role'] === 'admin';
if ((int)$job['poster_id'] !== $userId && !$isAdmin) {
    $out(['success' => false, 'message' => 'Only the person who posted this job can change it.'], 403);
}

try {
    switch ($action) {
        case 'update':
            [, $err] = Job::clean($data);
            if ($err) {
                $out(['success' => false, 'message' => $err], 400);
            }
            (new Job($db))->update($jobId, $data);
            $out(['success' => true, 'message' => 'Job updated.']);
        case 'close':
        case 'reopen':
            $db->prepare("UPDATE jobs SET status = :s, updated_at = CURRENT_TIMESTAMP WHERE job_id = :id")
               ->execute(['s' => $action === 'close' ? 'closed' : 'open', 'id' => $jobId]);
            $out(['success' => true, 'message' => $action === 'close' ? 'Job closed — it no longer accepts applications.' : 'Job reopened.']);
        case 'delete':
            require_once __DIR__ . '/_content_store.php';
            delete_content_payload($db, $job['description_file_path']);
            $db->prepare("DELETE FROM jobs WHERE job_id = :id")->execute(['id' => $jobId]);
            $auth->logAction($userId, 'JOB_DELETE', "Job #$jobId" . ($isAdmin ? ' (admin)' : ''));
            $out(['success' => true, 'message' => 'Job deleted.']);
        default:
            $out(['success' => false, 'message' => 'Unknown action.'], 400);
    }
} catch (Throwable $e) {
    error_log('manage_job: ' . $e->getMessage());
    $out(['success' => false, 'message' => 'Could not update the job right now.'], 500);
}
