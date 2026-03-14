<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';

function ensureMentorshipSchema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_profiles (
            mentor_user_id BIGINT PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
            headline TEXT NULL,
            expertise TEXT NULL,
            is_active BOOLEAN NOT NULL DEFAULT FALSE,
            approval_status VARCHAR(20) NOT NULL DEFAULT 'approved',
            approved_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
            approved_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");

    $db->exec("ALTER TABLE mentorship_profiles ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NOT NULL DEFAULT 'approved'");
    $db->exec("ALTER TABLE mentorship_profiles ADD COLUMN IF NOT EXISTS approved_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL");
    $db->exec("ALTER TABLE mentorship_profiles ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL");
    $db->exec("ALTER TABLE mentorship_profiles ALTER COLUMN is_active SET DEFAULT FALSE");

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

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_groups (
            group_id BIGSERIAL PRIMARY KEY,
            mentor_user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
            title TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_group_members (
            group_id BIGINT NOT NULL REFERENCES mentorship_groups(group_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            member_role VARCHAR(20) NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP NOT NULL DEFAULT NOW(),
            PRIMARY KEY (group_id, user_id)
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_profiles_active ON mentorship_profiles(is_active)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_profiles_status ON mentorship_profiles(approval_status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentor ON mentorship_requests(mentor_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentee ON mentorship_requests(mentee_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_group_members_user ON mentorship_group_members(user_id)");

    // Keep already-active legacy mentor rows visible after the approval layer lands.
    $db->exec("
        UPDATE mentorship_profiles
        SET approval_status = 'approved'
        WHERE is_active = TRUE
          AND COALESCE(NULLIF(TRIM(approval_status), ''), 'approved') <> 'approved'
    ");

    $db->exec("
        UPDATE mentorship_profiles
        SET is_active = FALSE
        WHERE approval_status <> 'approved'
          AND is_active = TRUE
    ");

    $done = true;
}

function getUserRole(PDO $db, int $userId): string
{
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    return strtolower((string)($stmt->fetchColumn() ?: ''));
}

function canReviewMentorApplications(string $role): bool
{
    return in_array($role, ['faculty', 'admin'], true);
}

function canSelfActivateAsMentor(string $role): bool
{
    return in_array($role, ['faculty', 'admin'], true);
}

function canApplyForMentorship(string $role): bool
{
    return in_array($role, ['faculty', 'admin', 'alumni'], true);
}

function getMentorProfile(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare("
        SELECT mentor_user_id, headline, expertise, is_active, approval_status, approved_by, approved_at, created_at, updated_at
        FROM mentorship_profiles
        WHERE mentor_user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ensureMentorGroup(PDO $db, int $mentorUserId): int
{
    $stmt = $db->prepare("
        SELECT g.group_id
        FROM mentorship_groups g
        WHERE g.mentor_user_id = :mentor_id
        LIMIT 1
    ");
    $stmt->execute([':mentor_id' => $mentorUserId]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $groupId = (int)$existing;
    } else {
        $titleStmt = $db->prepare("
            SELECT COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS display_name
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE u.user_id = :mentor_id
            LIMIT 1
        ");
        $titleStmt->execute([':mentor_id' => $mentorUserId]);
        $displayName = (string)($titleStmt->fetchColumn() ?: 'Mentor');
        $groupTitle = $displayName . "'s Mentor Group";

        $insert = $db->prepare("
            INSERT INTO mentorship_groups (mentor_user_id, title, updated_at)
            VALUES (:mentor_id, :title, NOW())
            RETURNING group_id
        ");
        $insert->execute([
            ':mentor_id' => $mentorUserId,
            ':title' => $groupTitle
        ]);
        $groupId = (int)$insert->fetchColumn();
    }

    $memberStmt = $db->prepare("
        INSERT INTO mentorship_group_members (group_id, user_id, member_role)
        VALUES (:group_id, :user_id, :member_role)
        ON CONFLICT (group_id, user_id)
        DO UPDATE SET member_role = EXCLUDED.member_role
    ");
    $memberStmt->execute([
        ':group_id' => $groupId,
        ':user_id' => $mentorUserId,
        ':member_role' => 'admin'
    ]);

    return $groupId;
}

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();

ensureMentorshipSchema($db);

$role = getUserRole($db, $userId);
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = [];
}
$action = (string)($_GET['action'] ?? '');

switch ($action) {
    case 'get_status':
        $profile = getMentorProfile($db, $userId);
        jsonResponse([
            'success' => true,
            'data' => [
                'role' => $role,
                'can_apply' => canApplyForMentorship($role),
                'can_self_activate' => canSelfActivateAsMentor($role),
                'can_review_applications' => canReviewMentorApplications($role),
                'mentor_profile' => $profile ? [
                    'headline' => $profile['headline'],
                    'expertise' => $profile['expertise'],
                    'is_active' => filter_var($profile['is_active'], FILTER_VALIDATE_BOOL),
                    'approval_status' => (string)$profile['approval_status'],
                    'approved_at' => $profile['approved_at'],
                    'updated_at' => $profile['updated_at']
                ] : null
            ]
        ]);
        break;

    case 'list_mentors':
        $stmt = $db->query("
            SELECT
                mp.mentor_user_id AS mentor_id,
                COALESCE(p.full_name, u.email) AS mentor_name,
                u.role,
                COALESCE(p.profile_picture_url, '') AS avatar,
                COALESCE(mp.headline, p.bio, '') AS headline,
                COALESCE(mp.expertise, p.tech_stack, '') AS expertise,
                g.group_id
            FROM mentorship_profiles mp
            JOIN users u ON u.user_id = mp.mentor_user_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            LEFT JOIN mentorship_groups g ON g.mentor_user_id = mp.mentor_user_id
            WHERE mp.is_active = TRUE
              AND mp.approval_status = 'approved'
              AND u.role IN ('faculty', 'alumni', 'admin')
              AND u.status = 'active'
            ORDER BY
                CASE WHEN u.role = 'faculty' THEN 0 WHEN u.role = 'admin' THEN 1 ELSE 2 END,
                mp.updated_at DESC
        ");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'become_mentor':
        if (!canApplyForMentorship($role)) {
            jsonResponse(['success' => false, 'message' => 'Only faculty, alumni, or admin can become mentors.'], 403);
        }

        $headline = trim((string)($data['headline'] ?? ''));
        $expertise = trim((string)($data['expertise'] ?? ''));
        $existing = getMentorProfile($db, $userId);

        if (canSelfActivateAsMentor($role)) {
            $stmt = $db->prepare("
                INSERT INTO mentorship_profiles (mentor_user_id, headline, expertise, is_active, approval_status, approved_by, approved_at, updated_at)
                VALUES (:uid, :headline, :expertise, TRUE, 'approved', :uid, NOW(), NOW())
                ON CONFLICT (mentor_user_id)
                DO UPDATE SET
                    headline = EXCLUDED.headline,
                    expertise = EXCLUDED.expertise,
                    is_active = TRUE,
                    approval_status = 'approved',
                    approved_by = EXCLUDED.approved_by,
                    approved_at = NOW(),
                    updated_at = NOW()
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':headline' => $headline ?: null,
                ':expertise' => $expertise ?: null
            ]);
            ensureMentorGroup($db, $userId);
            jsonResponse(['success' => true, 'message' => 'You are now available as a mentor.']);
        }

        $isAlreadyApproved = $existing && (string)$existing['approval_status'] === 'approved';
        if ($isAlreadyApproved) {
            $stmt = $db->prepare("
                UPDATE mentorship_profiles
                SET headline = :headline,
                    expertise = :expertise,
                    is_active = TRUE,
                    updated_at = NOW()
                WHERE mentor_user_id = :uid
            ");
            $stmt->execute([
                ':headline' => $headline ?: null,
                ':expertise' => $expertise ?: null,
                ':uid' => $userId
            ]);
            ensureMentorGroup($db, $userId);
            jsonResponse(['success' => true, 'message' => 'Your mentor profile was updated.']);
        }

        $stmt = $db->prepare("
            INSERT INTO mentorship_profiles (mentor_user_id, headline, expertise, is_active, approval_status, approved_by, approved_at, updated_at)
            VALUES (:uid, :headline, :expertise, FALSE, 'pending', NULL, NULL, NOW())
            ON CONFLICT (mentor_user_id)
            DO UPDATE SET
                headline = EXCLUDED.headline,
                expertise = EXCLUDED.expertise,
                is_active = FALSE,
                approval_status = 'pending',
                approved_by = NULL,
                approved_at = NULL,
                updated_at = NOW()
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':headline' => $headline ?: null,
            ':expertise' => $expertise ?: null
        ]);

        jsonResponse(['success' => true, 'message' => 'Mentor application submitted for faculty/admin approval.']);
        break;

    case 'list_mentor_applications':
        if (!canReviewMentorApplications($role)) {
            jsonResponse(['success' => false, 'message' => 'Only faculty or admin can review mentor applications.'], 403);
        }
        $stmt = $db->query("
            SELECT
                mp.mentor_user_id AS applicant_id,
                COALESCE(p.full_name, u.email) AS applicant_name,
                u.email,
                u.role,
                COALESCE(p.profile_picture_url, '') AS avatar,
                COALESCE(mp.headline, '') AS headline,
                COALESCE(mp.expertise, '') AS expertise,
                mp.approval_status,
                mp.created_at,
                mp.updated_at
            FROM mentorship_profiles mp
            JOIN users u ON u.user_id = mp.mentor_user_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE u.role = 'alumni'
              AND mp.approval_status = 'pending'
            ORDER BY mp.updated_at DESC, mp.created_at DESC
        ");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'review_application':
        if (!canReviewMentorApplications($role)) {
            jsonResponse(['success' => false, 'message' => 'Only faculty or admin can review mentor applications.'], 403);
        }
        $applicantId = (int)($data['mentor_user_id'] ?? 0);
        $status = strtolower(trim((string)($data['status'] ?? '')));
        if ($applicantId <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            jsonResponse(['success' => false, 'message' => 'mentor_user_id and status are required.'], 400);
        }

        $check = $db->prepare("
            SELECT u.role
            FROM users u
            JOIN mentorship_profiles mp ON mp.mentor_user_id = u.user_id
            WHERE u.user_id = :uid
            LIMIT 1
        ");
        $check->execute([':uid' => $applicantId]);
        $applicantRole = strtolower((string)$check->fetchColumn());
        if ($applicantRole !== 'alumni') {
            jsonResponse(['success' => false, 'message' => 'Only alumni mentor applications can be reviewed.'], 400);
        }

        $stmt = $db->prepare("
            UPDATE mentorship_profiles
            SET approval_status = :status,
                is_active = CASE WHEN :status = 'approved' THEN TRUE ELSE FALSE END,
                approved_by = CASE WHEN :status = 'approved' THEN :reviewer ELSE NULL END,
                approved_at = CASE WHEN :status = 'approved' THEN NOW() ELSE NULL END,
                updated_at = NOW()
            WHERE mentor_user_id = :mentor_user_id
        ");
        $stmt->execute([
            ':status' => $status,
            ':reviewer' => $userId,
            ':mentor_user_id' => $applicantId
        ]);

        if ($status === 'approved') {
            ensureMentorGroup($db, $applicantId);
        }

        jsonResponse([
            'success' => true,
            'message' => $status === 'approved'
                ? 'Mentor application approved.'
                : 'Mentor application rejected.'
        ]);
        break;

    case 'request':
        if ($role !== 'student') {
            jsonResponse(['success' => false, 'message' => 'Only students can request mentorship.'], 403);
        }
        $mentorId = (int)($data['mentor_id'] ?? 0);
        $message = trim((string)($data['message'] ?? 'I want to join under your mentorship.'));
        if ($mentorId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Missing mentor_id.'], 400);
        }

        $check = $db->prepare("
            SELECT 1
            FROM mentorship_profiles mp
            JOIN users u ON u.user_id = mp.mentor_user_id
            WHERE mp.mentor_user_id = :mid
              AND mp.is_active = TRUE
              AND mp.approval_status = 'approved'
              AND u.role IN ('faculty', 'alumni', 'admin')
              AND u.status = 'active'
        ");
        $check->execute([':mid' => $mentorId]);
        if (!$check->fetchColumn()) {
            jsonResponse(['success' => false, 'message' => 'Selected mentor is not available.'], 404);
        }

        $stmt = $db->prepare("
            INSERT INTO mentorship_requests (mentee_id, mentor_id, message, status, updated_at)
            VALUES (:mentee, :mentor, :msg, 'pending', NOW())
            ON CONFLICT (mentee_id, mentor_id)
            DO UPDATE SET message = EXCLUDED.message, status = 'pending', updated_at = NOW()
        ");
        $stmt->execute([
            ':mentee' => $userId,
            ':mentor' => $mentorId,
            ':msg' => $message
        ]);
        jsonResponse(['success' => true, 'message' => 'Mentorship request sent.']);
        break;

    case 'respond':
        if (!in_array($role, ['faculty', 'alumni', 'admin'], true)) {
            jsonResponse(['success' => false, 'message' => 'Only mentors can respond to requests.'], 403);
        }
        $requestId = (int)($data['request_id'] ?? 0);
        $status = strtolower(trim((string)($data['status'] ?? '')));
        if ($requestId <= 0 || !in_array($status, ['accepted', 'rejected'], true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid request_id or status.'], 400);
        }

        $mentorProfile = getMentorProfile($db, $userId);
        if (!canSelfActivateAsMentor($role) && (!$mentorProfile || !$mentorProfile['is_active'] || (string)$mentorProfile['approval_status'] !== 'approved')) {
            jsonResponse(['success' => false, 'message' => 'Only approved mentors can respond to requests.'], 403);
        }

        $reqStmt = $db->prepare("
            SELECT mentee_id, mentor_id
            FROM mentorship_requests
            WHERE request_id = :rid AND mentor_id = :mid
            LIMIT 1
        ");
        $reqStmt->execute([
            ':rid' => $requestId,
            ':mid' => $userId
        ]);
        $requestRow = $reqStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            jsonResponse(['success' => false, 'message' => 'Request not found.'], 404);
        }

        $stmt = $db->prepare("
            UPDATE mentorship_requests
            SET status = :status, updated_at = NOW()
            WHERE request_id = :rid AND mentor_id = :mid
        ");
        $stmt->execute([
            ':status' => $status,
            ':rid' => $requestId,
            ':mid' => $userId
        ]);

        $groupId = null;
        if ($status === 'accepted') {
            $groupId = ensureMentorGroup($db, $userId);
            $memberStmt = $db->prepare("
                INSERT INTO mentorship_group_members (group_id, user_id, member_role)
                VALUES (:group_id, :user_id, 'member')
                ON CONFLICT (group_id, user_id) DO NOTHING
            ");
            $memberStmt->execute([
                ':group_id' => $groupId,
                ':user_id' => (int)$requestRow['mentee_id']
            ]);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Request updated.',
            'data' => [
                'group_id' => $groupId
            ]
        ]);
        break;

    case 'list_requests':
        if (!in_array($role, ['faculty', 'alumni', 'admin'], true)) {
            jsonResponse(['success' => true, 'data' => []]);
        }

        $stmt = $db->prepare("
            SELECT
                r.request_id,
                r.mentee_id,
                r.mentor_id,
                r.message,
                r.status,
                r.created_at,
                g.group_id,
                COALESCE(p.full_name, u.email) AS mentee_name,
                COALESCE(p.profile_picture_url, '') AS mentee_avatar
            FROM mentorship_requests r
            JOIN users u ON u.user_id = r.mentee_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            LEFT JOIN mentorship_groups g ON g.mentor_user_id = r.mentor_id
            WHERE r.mentor_id = :mid
            ORDER BY
                CASE WHEN r.status = 'pending' THEN 0 WHEN r.status = 'accepted' THEN 1 ELSE 2 END,
                r.created_at DESC
        ");
        $stmt->execute([':mid' => $userId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'list_my_requests':
        if ($role !== 'student') {
            jsonResponse(['success' => true, 'data' => []]);
        }
        $stmt = $db->prepare("
            SELECT
                r.request_id,
                r.mentor_id,
                r.message,
                r.status,
                r.created_at,
                COALESCE(p.full_name, u.email) AS mentor_name,
                u.role AS mentor_role,
                g.group_id
            FROM mentorship_requests r
            JOIN users u ON u.user_id = r.mentor_id
            LEFT JOIN profiles p ON p.user_id = u.user_id
            LEFT JOIN mentorship_groups g ON g.mentor_user_id = r.mentor_id
            WHERE r.mentee_id = :uid
            ORDER BY
                CASE WHEN r.status = 'pending' THEN 0 WHEN r.status = 'accepted' THEN 1 ELSE 2 END,
                r.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}
