<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$post_id = isset($_GET['post_id']) ? $_GET['post_id'] : null;

if (!$post_id) {
    echo json_encode(["message" => "Post ID required."]);
    exit();
}

// 1. Fetch all comments for the post
$query = "SELECT c.*, p.full_name, p.profile_picture_url 
          FROM comments c 
          JOIN profiles p ON c.user_id = p.user_id 
          WHERE c.post_id = :pid 
          ORDER BY c.parent_comment_id ASC NULLS FIRST, c.created_at ASC";

$stmt = $db->prepare($query);
$stmt->execute(['pid' => $post_id]);
$all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Recursive Threading Logic
$threaded = [];
$lookup = [];

foreach ($all_comments as $row) {
    // 3.5NF Hydration: Read comment text from file
    $text = "Content missing.";
    $file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $row['content_file_path']);
    if (file_exists($file_path)) {
        $text = file_get_contents($file_path);
    }

    $comment = [
        "comment_id" => $row['comment_id'],
        "parent_id" => $row['parent_comment_id'],
        "user" => $row['full_name'],
        "avatar" => $row['profile_picture_url'],
        "text" => $text,
        "depth" => $row['depth_level'],
        "replies" => []
    ];

    $lookup[$row['comment_id']] = &$comment;

    if ($row['parent_comment_id'] == null) {
        $threaded[] = &$comment;
    } else {
        if (isset($lookup[$row['parent_comment_id']])) {
            $lookup[$row['parent_comment_id']]['replies'][] = &$comment;
        }
    }
    unset($comment);
}

echo json_encode(["post_id" => $post_id, "comments" => $threaded]);