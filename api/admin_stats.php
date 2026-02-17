<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// 1. Authorization: Blueprint Section 4.D (Admin Only)
$user_id = $auth->validateRequest();
$role_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$role_stmt->execute(['uid' => $user_id]);
$user = $role_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Admin access only."]);
    exit();
}

$stats = [];

// 2. Platform Scale Metrics
$stats['total_users'] = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_posts'] = (int)$db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$stats['active_jobs'] = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();
$stats['total_events'] = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();

// 3. Security & Compliance (Blueprint Section 6 & 13)
$stats['pending_approvals'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$stats['suspended_users'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

// 4. Growth Trends (Last 24 Hours)
$stats['new_users_today'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= CURRENT_DATE")->fetchColumn();

// 5. Engagement Analytics (Blueprint Section 11 & 14)
$stats['total_reactions'] = (int)$db->query("SELECT SUM(reaction_count) FROM posts")->fetchColumn() ?: 0;
$stats['total_comments'] = (int)$db->query("SELECT SUM(comment_count) FROM posts")->fetchColumn() ?: 0;

// 6. Role Distribution
$dist_stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stats['user_distribution'] = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($stats);
?>