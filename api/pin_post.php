<?php
/**
 * Pin Post API
 * Pin a post to user's profile
 * Alumni: Max 3 pinned posts
 * Faculty: Max 5 pinned posts
 */

require_once '../config/Database.php';
require_once '../middleware/Auth.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed']);
    exit;
}

$auth = new Auth($db);
$user_id = $auth->validateRequest();

$user_stmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = :uid");
$user_stmt->execute(['uid' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

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

try {
    // Check if post exists and belongs to user
    $check_query = "SELECT user_id FROM posts WHERE post_id = :post_id AND status = 'published'::post_status";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute(['post_id' => $data['post_id']]);
    $post = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['message' => 'Post not found']);
        exit;
    }
    
    if ($post['user_id'] != $user['user_id']) {
        http_response_code(403);
        echo json_encode(['message' => 'You can only pin your own posts']);
        exit;
    }
    
    // Check current pinned post count
    $count_query = "SELECT COUNT(*) as count FROM pinned_posts WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute(['user_id' => $user['user_id']]);
    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Determine max pins based on role
    $max_pins = ($user['role'] === 'faculty' || $user['role'] === 'admin') ? 5 : 3;
    
    if ($count >= $max_pins) {
        http_response_code(400);
        echo json_encode([
            'message' => "Maximum {$max_pins} pinned posts allowed",
            'max_pins' => $max_pins,
            'current_pins' => $count
        ]);
        exit;
    }
    
    // Get next pin order
    $order_query = "SELECT COALESCE(MAX(pin_order), 0) + 1 as next_order 
                   FROM pinned_posts WHERE user_id = :user_id";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->execute(['user_id' => $user['user_id']]);
    $pin_order = $order_stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
    
    // Pin the post
    $pin_query = "INSERT INTO pinned_posts (user_id, post_id, pin_order) 
                 VALUES (:user_id, :post_id, :pin_order)
                 ON CONFLICT (user_id, post_id) DO NOTHING";
    $pin_stmt = $db->prepare($pin_query);
    $success = $pin_stmt->execute([
        'user_id' => $user['user_id'],
        'post_id' => $data['post_id'],
        'pin_order' => $pin_order
    ]);
    
    if ($success && $pin_stmt->rowCount() > 0) {
        echo json_encode([
            'message' => 'Post pinned successfully',
            'pin_order' => $pin_order,
            'total_pinned' => $count + 1
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Post already pinned']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
