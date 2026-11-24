<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'admin']);   // who may call this — enforced before anything below
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $admin_id = $auth->validateRequest();

    $check = api_role_stmt($db, $admin_id);
    if ($check->fetchColumn() !== 'admin') {
        respond(["success" => false, "message" => "Only Admin can generate alumni tokens."], 403);
    }

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $action = strtolower((string)($_GET['action'] ?? ''));

    if ($method === 'GET' && $action === 'list') {
        $stmt = $db->query("
            SELECT token, used, used_by_email, created_at, used_at
            FROM alumni_tokens
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array_map(static function (array $row): array {
            return [
                'token' => (string)$row['token'],
                'email' => $row['used_by_email'] ?? null,
                'used_count' => ($row['used'] ? 1 : 0),
                'usage_limit' => 1,
                'is_active' => !$row['used'],
                'created_at' => $row['created_at'],
                'expires_at' => null,
                'used_at' => $row['used_at'] ?? null
            ];
        }, $rows);
        respond(["success" => true, "status" => "success", "data" => $data]);
    }

    if ($method !== 'POST') {
        respond(["success" => false, "message" => "Unsupported method."], 405);
    }

    $newToken = bin2hex(random_bytes(8));
    $query = "INSERT INTO alumni_tokens (token, generated_by_admin_id) VALUES (:t, :aid)";
    $stmt = $db->prepare($query);
    $ok = $stmt->execute(['t' => $newToken, 'aid' => $admin_id]);

    if (!$ok) {
        respond(["success" => false, "message" => "Token generation failed."], 500);
    }

    respond([
        "success" => true,
        "status" => "success",
        "message" => "Token generated for Alumni registration.",
        "token" => $newToken,
        "data" => ["token" => $newToken]
    ]);
} catch (Throwable $e) {
    respond(["success" => false, "message" => "Token generation failed.", "error_ref" => api_error_ref($e)], 500);
}

