<?php
header('Content-Type: application/json');
require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../helpers/StreamHelper.php';

$database = new Database();
$db = $database->getConnection();
$streamHelper = new StreamHelper($db);

try {
    $streams = $streamHelper->getActiveStreams();
    echo json_encode(['status' => 'success', 'data' => $streams]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}