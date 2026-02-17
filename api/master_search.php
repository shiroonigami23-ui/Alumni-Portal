<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->validateRequest();

$term = isset($_GET['q']) ? $_GET['q'] : '';
$q = "%$term%";

$results = ["users" => [], "jobs" => [], "events" => []];

if (strlen($term) >= 2) {
    // 1. Users (Join Profile for Names)
    $u_stmt = $db->prepare("SELECT p.full_name, u.role FROM users u JOIN profiles p ON u.user_id = p.user_id WHERE p.full_name ILIKE :q AND p.is_private = false LIMIT 5");
    $u_stmt->execute(['q' => $q]);
    $results['users'] = $u_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Jobs
    $j_stmt = $db->prepare("SELECT job_id, company_name, job_title FROM jobs WHERE (job_title ILIKE :q OR company_name ILIKE :q) AND status = 'open' LIMIT 5");
    $j_stmt->execute(['q' => $q]);
    $results['jobs'] = $j_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Events (Table: campus_events)
    $e_stmt = $db->prepare("SELECT event_id, title, event_date FROM campus_events WHERE title ILIKE :q LIMIT 5");
    $e_stmt->execute(['q' => $q]);
    $results['events'] = $e_stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode(["results" => $results]);
?>