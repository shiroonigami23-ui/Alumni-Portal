<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$admin_id = $auth->validateRequest();

// Blueprint Section 4.D: Only Admin can see the reporter identities
$u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$u_stmt->execute(['uid' => $admin_id]);
if ($u_stmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Forbidden."]);
    exit();
}

// Fetch reports with post titles, reporter, and post owner details
$query = "SELECT
            r.report_id,
            r.reported_post_id as post_id,
            p.title as post_title,
            p.user_id as author_id,
            owner.email as author_email,
            owner.role as author_role,
            r.reason,
            r.custom_reason,
            r.status,
            r.created_at,
            u.email as reporter_email,
            r.reporter_user_id as reporter_id,
            COALESCE(p.report_count, 0) AS report_count
          FROM reports r
          JOIN posts p ON r.reported_post_id = p.post_id
          JOIN users u ON r.reporter_user_id = u.user_id
          JOIN users owner ON owner.user_id = p.user_id
          ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($reports);
?>
