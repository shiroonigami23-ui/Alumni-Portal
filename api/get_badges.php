<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// 1. Calculate Aggregate Likes from Active + Archived (Blueprint Section 9)
$query = "SELECT 
            (SELECT COALESCE(SUM(reaction_count), 0) FROM posts WHERE user_id = :uid) + 
            (SELECT COALESCE(SUM(final_reaction_count), 0) FROM archived_posts WHERE user_id = :uid) 
          as total_likes";

$stmt = $db->prepare($query);
$stmt->execute(['uid' => $user_id]);
$total_likes = (int)$stmt->fetchColumn();

// 2. Logic for Badges (Section 9 Thresholds)
$badges = [];
if ($total_likes >= 1) $badges[] = ["name" => "First Step", "icon" => "star", "desc" => "Got your first like."];
if ($total_likes >= 100) $badges[] = ["name" => "Influencer", "icon" => "fire", "desc" => "Reached 100+ likes."];
if ($total_likes >= 1000) $badges[] = ["name" => "Elite Architect", "icon" => "crown", "desc" => "Legendary status: 1k+ likes."];

echo json_encode([
    "total_likes_all_time" => $total_likes,
    "earned_badges" => $badges
]);
?>