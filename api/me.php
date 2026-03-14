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

function clear_missing_local_asset(?string $path): string
{
    $path = trim(str_replace('\\', '/', (string)$path));
    if ($path === '' || stripos($path, 'data:image/') === 0) {
        return $path;
    }
    if (preg_match('#\.php(?:$|\?)#i', $path)) {
        return $path;
    }
    if (preg_match('#^https?://#i', $path)) {
        $parsedPath = (string)(parse_url($path, PHP_URL_PATH) ?? '');
        if ($parsedPath === '' || preg_match('#\.php$#i', $parsedPath)) {
            return $path;
        }
        $path = ltrim(str_replace('\\', '/', $parsedPath), '/');
    }
    $path = ltrim($path, '/');
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    return is_file($abs) ? $path : '';
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Database unavailable. Please start PostgreSQL and try again."
    ]);
    exit;
}

$auth = new Auth($db);

try {
    $user_id = $auth->validateRequest();

    $query = "SELECT 
                u.user_id,
                u.email,
                u.role,
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
    $picture = clear_missing_local_asset($picture);
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
