<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

function ensureMentorshipSchema(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_profiles (
            mentor_user_id BIGINT PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
            headline TEXT NULL,
            expertise TEXT NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_requests (
            request_id BIGSERIAL PRIMARY KEY,
            mentee_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            mentor_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','accepted','rejected')),
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (mentee_id, mentor_id)
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_profiles_active ON mentorship_profiles(is_active)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentor ON mentorship_requests(mentor_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentee ON mentorship_requests(mentee_id, status)");
}

function getUserRole(PDO $db, int $userId): string
{
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    return (string)($stmt->fetchColumn() ?: '');
}

ensureMentorshipSchema($db);
$role = strtolower(getUserRole($db, (int)$user_id));

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_mentors':
        $stmt = $db->query("
            SELECT
                mp.mentor_user_id AS mentor_id,
                COALESCE(p.full_name, u.email) AS mentor_name,
                u.role,
                COALESCE(p.profile_picture_url, '') AS avatar,
                COALESCE(mp.headline, p.bio, '') AS headline,
                COALESCE(mp.expertise, p.tech_stack, '') AS expertise
            FROM mentorship_profiles mp
            JOIN users u ON u.user_id = mp.mentor_user_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE mp.is_active = TRUE
              AND u.role IN ('faculty', 'alumni', 'admin')
              AND u.status = 'active'
            ORDER BY mp.updated_at DESC
        ");
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'become_mentor':
        if (!in_array($role, ['faculty', 'alumni', 'admin'], true)) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Only faculty/alumni/admin can become mentors."]);
            break;
        }
        $headline = trim((string)($data->headline ?? ''));
        $expertise = trim((string)($data->expertise ?? ''));
        $stmt = $db->prepare("
            INSERT INTO mentorship_profiles (mentor_user_id, headline, expertise, is_active, updated_at)
            VALUES (:uid, :headline, :expertise, TRUE, NOW())
            ON CONFLICT (mentor_user_id)
            DO UPDATE SET headline = EXCLUDED.headline, expertise = EXCLUDED.expertise, is_active = TRUE, updated_at = NOW()
        ");
        $stmt->execute([
            ':uid' => (int)$user_id,
            ':headline' => $headline ?: null,
            ':expertise' => $expertise ?: null
        ]);
        echo json_encode(["success" => true, "message" => "You are now available as a mentor."]);
        break;

    case 'request':
        if ($role !== 'student') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Only students can request mentorship."]);
            break;
        }
        $mentorId = (int)($data->mentor_id ?? 0);
        $message = trim((string)($data->message ?? 'I want to join under your mentorship.'));
        if ($mentorId <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing mentor_id."]);
            break;
        }
        $check = $db->prepare("
            SELECT 1
            FROM mentorship_profiles mp
            JOIN users u ON u.user_id = mp.mentor_user_id
            WHERE mp.mentor_user_id = :mid
              AND mp.is_active = TRUE
              AND u.role IN ('faculty', 'alumni', 'admin')
        ");
        $check->execute([':mid' => $mentorId]);
        if (!$check->fetchColumn()) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Selected mentor is not available."]);
            break;
        }
        $stmt = $db->prepare("
            INSERT INTO mentorship_requests (mentee_id, mentor_id, message, status, updated_at)
            VALUES (:mentee, :mentor, :msg, 'pending', NOW())
            ON CONFLICT (mentee_id, mentor_id)
            DO UPDATE SET message = EXCLUDED.message, status = 'pending', updated_at = NOW()
        ");
        $stmt->execute([
            ':mentee' => (int)$user_id,
            ':mentor' => $mentorId,
            ':msg' => $message
        ]);
        echo json_encode(["success" => true, "message" => "Mentorship request sent."]);
        break;

    case 'respond':
        if (!in_array($role, ['faculty', 'alumni', 'admin'], true)) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Only mentors can respond to requests."]);
            break;
        }
        $requestId = (int)($data->request_id ?? 0);
        $status = strtolower((string)($data->status ?? ''));
        if ($requestId <= 0 || !in_array($status, ['accepted', 'rejected'], true)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid request_id or status."]);
            break;
        }
        $stmt = $db->prepare("
            UPDATE mentorship_requests
            SET status = :status, updated_at = NOW()
            WHERE request_id = :rid AND mentor_id = :mid
        ");
        $stmt->execute([
            ':status' => $status,
            ':rid' => $requestId,
            ':mid' => (int)$user_id
        ]);
        echo json_encode(["success" => true, "message" => "Request updated."]);
        break;

    case 'list_requests':
        if (!in_array($role, ['faculty', 'alumni', 'admin'], true)) {
            echo json_encode(["success" => true, "data" => []]);
            break;
        }
        $stmt = $db->prepare("
            SELECT
                r.request_id,
                r.mentee_id,
                r.mentor_id,
                r.message,
                r.status,
                r.created_at,
                COALESCE(p.full_name, u.email) AS mentee_name,
                COALESCE(p.profile_picture_url, '') AS mentee_avatar
            FROM mentorship_requests r
            JOIN users u ON u.user_id = r.mentee_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE r.mentor_id = :mid
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([':mid' => (int)$user_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'list_my_requests':
        if ($role !== 'student') {
            echo json_encode(["success" => true, "data" => []]);
            break;
        }
        $stmt = $db->prepare("
            SELECT
                r.request_id,
                r.mentor_id,
                r.message,
                r.status,
                r.created_at,
                COALESCE(p.full_name, u.email) AS mentor_name,
                u.role AS mentor_role
            FROM mentorship_requests r
            JOIN users u ON u.user_id = r.mentor_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE r.mentee_id = :uid
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([':uid' => (int)$user_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        break;
}
