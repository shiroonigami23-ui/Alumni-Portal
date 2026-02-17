<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/Resource.php';
include_once '../middleware/Security.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$resource = new Resource($db);

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        Security::checkCSRF();

        if (!empty($data->title) && !empty($data->file_url) && !empty($data->category)) {
            $resource_id = $resource->create(
                $user_id,
                $data->title,
                $data->description ?? '',
                $data->category,
                $data->file_url,
                $data->resource_type ?? 'document'
            );

            if ($resource_id) {
                echo json_encode([
                    "message" => "Resource created successfully.",
                    "resource_id" => $resource_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create resource."]);
            }
        } else {
            echo json_encode(["message" => "Incomplete data."]);
        }
        break;

    case 'list':
        $category = $_GET['category'] ?? null;
        $type = $_GET['type'] ?? null;
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;

        $resources = $resource->getAll($category, $type, $limit, $offset);

        // Sanitize output
        $resources = array_map([Security::class, 'sanitizeInput'], $resources);

        echo json_encode($resources);
        break;

    case 'download':
        if (!empty($data->resource_id)) {
            $resource->trackDownload($data->resource_id);
            echo json_encode(["message" => "Download tracked."]);
        }
        break;

    default:
        echo json_encode(["message" => "Invalid action."]);
        break;
}
