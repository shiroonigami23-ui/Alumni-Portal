<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $current_user_id = (int)$auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $target_user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

    if ($target_user_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "user_id is required."]);
        exit;
    }
    if ($target_user_id === $current_user_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "You cannot follow yourself."]);
        exit;
    }

    $exists = $db->prepare("SELECT user_id FROM users WHERE user_id = :uid");
    $exists->execute([':uid' => $target_user_id]);
    if (!$exists->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "User not found."]);
        exit;
    }

    $tableExists = $db->query("SELECT to_regclass('public.connections')")->fetchColumn();
    if (!$tableExists) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS connections (
                connection_id BIGSERIAL PRIMARY KEY,
                requester_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
                addressee_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
                status VARCHAR(20) NOT NULL DEFAULT 'accepted',
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                accepted_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (requester_user_id, addressee_user_id)
            )
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_connections_requester ON connections(requester_user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_connections_addressee ON connections(addressee_user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_connections_status ON connections(status)");
    }

    $check = $db->prepare("
        SELECT connection_id
        FROM connections
        WHERE requester_user_id = :rid
          AND addressee_user_id = :aid
          AND status = 'accepted'
        LIMIT 1
    ");
    $check->execute([':rid' => $current_user_id, ':aid' => $target_user_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $del = $db->prepare("DELETE FROM connections WHERE connection_id = :cid");
        $del->execute([':cid' => (int)$existing['connection_id']]);
        echo json_encode([
            "success" => true,
            "status" => "success",
            "connected" => false,
            "message" => "Unfollowed."
        ]);
        exit;
    }

    $ins = $db->prepare("
        INSERT INTO connections (requester_user_id, addressee_user_id, status, accepted_at)
        VALUES (:rid, :aid, 'accepted', NOW())
        ON CONFLICT (requester_user_id, addressee_user_id)
        DO UPDATE SET status = 'accepted', accepted_at = NOW()
    ");
    $ins->execute([':rid' => $current_user_id, ':aid' => $target_user_id]);

    echo json_encode([
        "success" => true,
        "status" => "success",
        "connected" => true,
        "message" => "Now following."
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

