<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept");
header("Access-Control-Max-Age: 3600");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_profile_media.php';
include_once __DIR__ . '/_community_schema.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Database unavailable. Please start your database service and try again."
    ]);
    exit;
}
ensure_community_schema($db);

$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();

    $query = "SELECT 
                u.user_id,
                u.email,
                u.role,
                u.is_moderator,
                u.status,
                u.can_post,
                u.created_at,
                p.full_name,
                p.profile_picture_url,
                p.bio,
                p.linkedin_url AS linkedin,
                p.github_url AS github,
                p.twitter_url AS twitter
              FROM users u
              LEFT JOIN profiles p ON u.user_id = p.user_id
              WHERE u.user_id = :user_id
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "User not found."
        ]);
        exit;
    }

    if (empty($user_data['full_name'])) {
        $user_data['full_name'] = explode('@', (string)$user_data['email'])[0];
    }
    $user_data['name'] = $user_data['full_name'];

    $picture = (string)($user_data['profile_picture_url'] ?? '');
    if ($picture !== '') {
        $picture = str_replace('\\', '/', $picture);
    }

    $looksPlaceholder = (
        $picture === '' ||
        stripos($picture, 'via.placeholder.com') !== false ||
        stripos($picture, 'placeholder') !== false ||
        stripos($picture, 'data:image/svg+xml') === 0
    );

    if ($looksPlaceholder && ($user_data['role'] ?? '') === 'faculty') {
        $emailSlug = strtolower((string)$user_data['email']);
        $emailSlug = preg_replace('/[^a-z0-9]+/', '_', $emailSlug);
        $candidates = [
            "storage/profiles/faculty_{$emailSlug}.jpg",
            "storage/profiles/faculty_{$emailSlug}.jpeg",
            "storage/profiles/faculty_{$emailSlug}.png",
            "storage/profiles/faculty_{$emailSlug}.JPG",
        ];
        foreach ($candidates as $candidate) {
            $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (file_exists($abs)) {
                $picture = $candidate;
                break;
            }
        }
    }
    $picture = resolve_profile_media_url($db, (int)$user_id, $picture, 'profile_picture_url', 'profile_avatar');
    $user_data['profile_picture_url'] = $picture;
    $user_data['profile_picture'] = $picture;

    try {
        $student_query = "SELECT roll_number, course, branch, graduation_year
                          FROM students
                          WHERE user_id = :user_id";
        $student_stmt = $db->prepare($student_query);
        $student_stmt->execute(['user_id' => $user_id]);

        if ($student_stmt->rowCount() > 0) {
            $student_data = $student_stmt->fetch(PDO::FETCH_ASSOC);
            $user_data = array_merge($user_data, $student_data);
        }
    } catch (Throwable $e) {
        // Students table may be missing in some environments; ignore.
    }

    echo json_encode([
        "success" => true,
        "status" => "success",
        "data" => $user_data
    ]);
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
