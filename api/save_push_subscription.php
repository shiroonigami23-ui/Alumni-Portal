<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

function ensure_push_table(PDO $db): void
{
    static $done = false;
    if ($done) return;
    $db->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            push_subscription_id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            endpoint TEXT NOT NULL,
            p256dh TEXT NULL,
            auth TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (user_id, endpoint)
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_push_subscriptions_user ON push_subscriptions(user_id)");
    $done = true;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    ensure_push_table($db);
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();

    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $endpoint = trim((string)($data['endpoint'] ?? ''));
    $p256dh = trim((string)($data['keys']['p256dh'] ?? ''));
    $authKey = trim((string)($data['keys']['auth'] ?? ''));

    if ($endpoint === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "endpoint is required"]);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, updated_at)
        VALUES (:uid, :endpoint, :p256dh, :auth, NOW())
        ON CONFLICT (user_id, endpoint)
        DO UPDATE SET p256dh = EXCLUDED.p256dh, auth = EXCLUDED.auth, updated_at = NOW()
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':endpoint' => $endpoint,
        ':p256dh' => $p256dh ?: null,
        ':auth' => $authKey ?: null
    ]);

    echo json_encode(["success" => true, "status" => "success"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => $e->getMessage()]);
}

