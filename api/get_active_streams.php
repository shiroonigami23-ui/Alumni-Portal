<?php
/** Live sessions happening now (signed-in members). Stream keys are never returned. */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../models/Stream.php';

$db = (new Database())->getConnection();
$userId = (int)(new Auth($db))->validateRequest();
try {
    $stream = new Stream($db);
    echo json_encode(['success' => true, 'status' => 'success', 'data' => $stream->active(), 'mine' => $stream->forUser($userId)]);
} catch (Throwable $e) {
    error_log('get_active_streams: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load live sessions.']);
}
