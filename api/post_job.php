<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => ['alumni', 'faculty', 'admin']]);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../config/DbCompat.php';
include_once '../middleware/Auth.php';
include_once '../models/Job.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$user_id = (int)$auth->validateRequest();

$stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
if (!in_array($stmt->fetchColumn(), ['alumni', 'faculty', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only alumni and faculty can post jobs.']);
    exit;
}

$data = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
[, $error] = Job::clean($data);
if ($error) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

try {
    $jobId = (new Job($db))->create($data, $user_id);
    $auth->logAction($user_id, 'JOB_POST', "Job #$jobId");
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Job opportunity posted successfully!', 'job_id' => $jobId]);
} catch (Throwable $e) {
    error_log('post_job: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not post the job right now.']);
}
