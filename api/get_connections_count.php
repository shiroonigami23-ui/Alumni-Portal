<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $userId = (int)$auth->validateRequest();

    $count = 0;
    $tableExists = (bool)$db->query("SELECT to_regclass('public.connections')")->fetchColumn();
    if ($tableExists) {
        $stmt = $db->prepare("
            SELECT COUNT(*)::int
            FROM connections
            WHERE status = 'accepted'
              AND (requester_user_id = :uid OR addressee_user_id = :uid)
        ");
        $stmt->execute([':uid' => $userId]);
        $count = (int)$stmt->fetchColumn();
    }

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'count' => $count
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Failed to load connections count.'
    ]);
}
