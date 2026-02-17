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

// Required: poll_id and the chosen option_id
if (!empty($data->poll_id) && !empty($data->option_id)) {
    try {
        $db->beginTransaction();

        // 1. Check if poll is expired (Blueprint Section 5)
        $check_expiry = $db->prepare("SELECT expires_at FROM polls WHERE poll_id = :pid");
        $check_expiry->execute(['pid' => $data->poll_id]);
        $poll = $check_expiry->fetch(PDO::FETCH_ASSOC);

        if (!$poll || strtotime($poll['expires_at']) < time()) {
            echo json_encode(["message" => "Poll has expired or does not exist."]);
            exit();
        }

        // 2. Record the vote (UNIQUE constraint on user_id + poll_id enforces "vote once")
        $vote_query = "INSERT INTO poll_votes (user_id, poll_id, option_id) VALUES (:uid, :pid, :oid)";
        $vote_stmt = $db->prepare($vote_query);
        $vote_stmt->execute([
            'uid' => $user_id,
            'pid' => $data->poll_id,
            'oid' => $data->option_id
        ]);

        // 3. Increment the vote count in poll_options for real-time results
        $update_query = "UPDATE poll_options SET vote_count = vote_count + 1 WHERE option_id = :oid";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute(['oid' => $data->option_id]);

        $db->commit();
        echo json_encode(["message" => "Vote recorded successfully."]);

    } catch (PDOException $e) {
        $db->rollBack();
        // Handle UNIQUE constraint violation (Blueprint Section 5)
        if ($e->getCode() == '23505') {
            http_response_code(400);
            echo json_encode(["message" => "You have already voted in this poll."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Poll ID and Option ID required."]);
}
?>