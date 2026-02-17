<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest(); // The Job Poster
$job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

if (!$job_id) {
    echo json_encode(["message" => "Job ID required."]);
    exit();
}

// 1. Verify Ownership: Only the poster (or Admin) can see applicants
$check = $db->prepare("SELECT poster_id, job_title FROM jobs WHERE job_id = :jid");
$check->execute(['jid' => $job_id]);
$job = $check->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(["message" => "Job not found."]);
    exit();
}

// Get user role to allow Admins to peek too
$u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$u_stmt->execute(['uid' => $user_id]);
$user_role = $u_stmt->fetchColumn();

// ARCHITECT RULE: Only the poster or an admin can view sensitive applicant data
if ($job['poster_id'] != $user_id && $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized: You did not post this job."]);
    exit();
}

// 2. Fetch Applicants with Profile Data
$query = "SELECT ja.application_id, ja.cover_letter, ja.resume_path, ja.created_at,
                 p.full_name, p.tech_stack, p.profile_picture_url, u.email
          FROM job_applications ja
          JOIN users u ON ja.applicant_id = u.user_id
          LEFT JOIN profiles p ON ja.applicant_id = p.user_id
          WHERE ja.job_id = :jid
          ORDER BY ja.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute(['jid' => $job_id]);
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "job_title" => $job['job_title'],
    "count" => count($applicants),
    "applicants" => $applicants
]);
?>