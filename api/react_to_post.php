<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->post_id)) {
    try {
        $db->beginTransaction();

        // 1. Insert into likes table (Unique constraint handles "1 like per ID")
        $query = "INSERT INTO likes (user_id, post_id) VALUES (:uid, :pid)";
        $stmt = $db->prepare($query);
        $stmt->execute(['uid' => $user_id, 'pid' => $data->post_id]);

        // 2. Increment count in posts table for real-time feed metrics
        $update = "UPDATE posts SET reaction_count = reaction_count + 1 WHERE post_id = :pid";
        $u_stmt = $db->prepare($update);
        $u_stmt->execute(['pid' => $data->post_id]);

        $db->commit();
        echo json_encode(["message" => "Post liked."]);
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() == '23505') { // Unique violation
            // Optional: Implement "Unlike" logic here
            echo json_encode(["message" => "You have already liked this post."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    }
}
?>