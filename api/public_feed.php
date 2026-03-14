<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once __DIR__ . '/_content_store.php';

$database = new Database();
$db = $database->getConnection();

// 1. Fetch metadata from DB
$query = "SELECT p.*, u.email as author_email, prof.full_name, prof.profile_picture_url 
          FROM posts p 
          JOIN users u ON p.user_id = u.user_id 
          LEFT JOIN profiles prof ON u.user_id = prof.user_id 
          WHERE p.status = 'published' 
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$feed = [];

foreach ($results as $row) {
    $payload = load_content_payload($db, (string)$row['content_file_path']);
    $content = trim((string)$payload['content']) !== '' ? (string)$payload['content'] : 'Content missing.';

    $feed[] = [
        "post_id" => $row['post_id'],
        "title" => $row['title'],
        "content" => $content, // The actual text from the file
        "author" => $row['full_name'] ?? $row['author_email'],
        "avatar" => $row['profile_picture_url'],
        "type" => $row['post_type'],
        "media" => $row['media_url'],
        "comments_allowed" => $row['comments_enabled'],
        "metrics" => [
            "reactions" => $row['reaction_count'],
            "comments" => $row['comment_count']
        ],
        "created_at" => $row['created_at']
    ];
}

echo json_encode(["count" => count($feed), "posts" => $feed]);
?>
