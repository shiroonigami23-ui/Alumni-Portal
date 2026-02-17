<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../models/SuccessStory.php';
include_once '../middleware/Security.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();
$story = new SuccessStory($db);

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        Security::checkCSRF();

        if (!empty($data->title) && !empty($data->content)) {
            $story_id = $story->create(
                $user_id,
                $data->title,
                $data->content,
                $data->category ?? 'career',
                $data->featured_image ?? null
            );

            if ($story_id) {
                echo json_encode([
                    "message" => "Success story submitted for review.",
                    "story_id" => $story_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to create story."]);
            }
        } else {
            echo json_encode(["message" => "Incomplete data."]);
        }
        break;

    case 'list':
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;
        $category = $_GET['category'] ?? null;

        $stories = $story->getAll($limit, $offset, $category);

        // Fetch content for each story
        foreach ($stories as &$s) {
            $s['content'] = $story->getContent($s['content_file_path']);
            // Sanitize output
            $s = Security::sanitizeInput($s);
        }

        echo json_encode($stories);
        break;

    case 'approve':
        Security::checkCSRF();

        // Check if user is admin
        $roleCheck = "SELECT role FROM users WHERE user_id = :uid";
        $stmt = $db->prepare($roleCheck);
        $stmt->execute(['uid' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Unauthorized."]);
            break;
        }

        if (!empty($data->story_id)) {
            if ($story->approve($data->story_id)) {
                echo json_encode(["message" => "Story approved."]);
            } else {
                echo json_encode(["message" => "Failed to approve story."]);
            }
        }
        break;

    default:
        echo json_encode(["message" => "Invalid action."]);
        break;
}
