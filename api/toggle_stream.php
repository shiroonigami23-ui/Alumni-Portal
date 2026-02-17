<?php
header('Content-Type: application/json');
require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../models/Stream.php';
require_once '../helpers/Logger.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    // Validate the token and get User ID
    $user_id = $auth->validateRequest();

    // Get the request body
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    if (!$data) {
        throw new Exception("Invalid JSON input");
    }

    $action = $data->action ?? 'stop';
    $title = $data->title ?? 'Untitled Stream';
    $description = $data->description ?? 'No description provided';

    $streamModel = new Stream($db);
    $success = false;

    if ($action === 'start') {
        $success = $streamModel->start($user_id, $title, $description);
        if ($success) {
            Logger::log($user_id, "STREAM_START", "Title: $title");
        }
    } else {
        $success = $streamModel->end($user_id);
        if ($success) {
            Logger::log($user_id, "STREAM_STOP", "Stream ended");
        }
    }

    echo json_encode([
        'status' => $success ? 'success' : 'error',
        'message' => $success ? "Stream $action successful" : "Failed to $action stream"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}