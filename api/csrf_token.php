<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../middleware/Security.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $auth->validateRequest();

    $csrf = Security::generateCSRFToken();
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'csrf_token' => $csrf
    ]);
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
}

