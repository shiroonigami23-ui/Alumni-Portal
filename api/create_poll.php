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

if (!empty($data->post_id) && !empty($data->question) && !empty($data->options)) {
    try {
        $db->beginTransaction();

        $query = "INSERT INTO polls (post_id, question, expires_at) VALUES (:pid, :q, :exp) RETURNING poll_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'pid' => $data->post_id,
            'q' => $data->question,
            'exp' => $data->expires_at
        ]);
        $poll_id = $stmt->fetchColumn();

        $opt_query = "INSERT INTO poll_options (poll_id, option_text, option_order) VALUES (:plid, :txt, :ord)";
        $opt_stmt = $db->prepare($opt_query);

        $index = 1;
        foreach ($data->options as $opt) {
            $opt_stmt->execute([
                'plid' => $poll_id, 
                'txt' => $opt, 
                'ord' => $index
            ]);
            $index++;
        }

        $db->commit();
        $auth->logAction($user_id, "CREATE_POLL", "Poll created for Post ID: " . $data->post_id);
        echo json_encode(["message" => "Poll created successfully.", "poll_id" => $poll_id]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Failed to create poll: " . $e->getMessage()]);
    }
}
?>
