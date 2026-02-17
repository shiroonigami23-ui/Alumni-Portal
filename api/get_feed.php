<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

// 1. Fetch User Metadata for Personalization (Section 11)
$u_stmt = $db->prepare("SELECT graduation_year, department FROM profiles WHERE user_id = :uid");
$u_stmt->execute(['uid' => $user_id]);
$user_meta = $u_stmt->fetch(PDO::FETCH_ASSOC);

$year = $user_meta['graduation_year'] ?? 0;

// 2. Personalized Scoring Query (Blueprint Section 11 Weights)
// Recency (40%), Relevance (35%), Engagement (20%)
$query = "SELECT p.*, pr.full_name, pr.profile_picture_url, u.role,
          (
            (EXTRACT(EPOCH FROM (NOW() - p.created_at)) * -0.0001) + -- Recency Weight
            (CASE WHEN pr.graduation_year = :year THEN 0.35 ELSE 0 END) + -- Relevance Weight
            (p.reaction_count * 0.02) -- Engagement Weight
          ) as feed_score
          FROM posts p
          JOIN profiles pr ON p.user_id = pr.user_id
          JOIN users u ON p.user_id = u.user_id
          WHERE p.status = 'published'
          ORDER BY p.is_pinned DESC, feed_score DESC
          LIMIT 20";

$stmt = $db->prepare($query);
$stmt->execute(['year' => $year]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$feed = [];
foreach ($posts as $post) {
    // Read 3.5NF content from file (Section 1)
    $content_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $post['content_file_path'];
    $body = file_exists($content_path) ? file_get_contents($content_path) : "";

    $feed[] = [
        "post_id" => $post['post_id'],
        "author" => $post['full_name'],
        "role" => $post['role'],
        "content" => $body,
        "type" => $post['post_type'],
        "metrics" => [
            "likes" => $post['reaction_count'],
            "comments" => $post['comment_count']
        ],
        "is_pinned" => $post['is_pinned'],
        "created_at" => $post['created_at']
    ];
}

echo json_encode($feed);
?>