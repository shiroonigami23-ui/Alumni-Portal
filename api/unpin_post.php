<?php
/**
 * Unpin Post API
 * Remove a pinned post from profile
 */

require_once '../config/Database.php';
require_once '../middleware/Auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$auth = new Auth();
$user = $auth->authenticate();

if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

if (!isset($data['post_id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'post_id required']);
    exit;
}

$database = new Database();
$db = $database->connect();

try {
    // Unpin the post
    $query = "DELETE FROM pinned_posts 
             WHERE user_id = :user_id AND post_id = :post_id";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        'user_id' => $user['user_id'],
        'post_id' => $data['post_id']
    ]);
    
    if ($success && $stmt->rowCount() > 0) {
        // Reorder remaining pins
        $reorder_query = "WITH ordered_pins AS (
                            SELECT post_id, ROW_NUMBER() OVER (ORDER BY pin_order) as new_order
                            FROM pinned_posts
                            WHERE user_id = :user_id
                         )
                         UPDATE pinned_posts pp
                         SET pin_order = op.new_order
                         FROM ordered_pins op
                         WHERE pp.post_id = op.post_id AND pp.user_id = :user_id";
        $reorder_stmt = $db->prepare($reorder_query);
        $reorder_stmt->execute(['user_id' => $user['user_id']]);
        
        echo json_encode(['message' => 'Post unpinned successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Pinned post not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}