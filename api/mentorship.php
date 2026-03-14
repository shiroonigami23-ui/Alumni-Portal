<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/_mentorship_schema.php';
require_once __DIR__ . '/_profile_media.php';

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
        $activeMatch = canRequestMentorship($role) ? getCurrentActiveMatch($db, $userId) : null;
        jsonResponse([
            'success' => true,
            'data' => [
                'role' => $role,
                'can_apply' => canApplyForMentorship($role),
                'can_request' => canRequestMentorship($role),
                'can_self_activate' => canSelfActivateAsMentor($role),
                'can_review_applications' => canReviewMentorApplications($role),
                'mentor_profile' => $profile ? [
                    'headline' => $profile['headline'],
                    'expertise' => $profile['expertise'],
                    'is_active' => filter_var($profile['is_active'], FILTER_VALIDATE_BOOL),
                    'approval_status' => (string)$profile['approval_status'],
                    'approved_at' => $profile['approved_at'],
                    'updated_at' => $profile['updated_at']
                ] : null,
                'active_match' => $activeMatch
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
                g.group_id,
                g.admin_user_id
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
        $rows = array_map(function (array $row) use ($db): array {
            $row['avatar'] = resolve_profile_media_url($db, (int)$row['mentor_id'], $row['avatar'] ?? '', 'profile_picture_url', 'profile_avatar');
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        jsonResponse(['success' => true, 'data' => $rows]);
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
        $rows = array_map(function (array $row) use ($db): array {
            $row['avatar'] = resolve_profile_media_url($db, (int)$row['applicant_id'], $row['avatar'] ?? '', 'profile_picture_url', 'profile_avatar');
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        jsonResponse(['success' => true, 'data' => $rows]);
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
            'message' => $status === 'approved' ? 'Mentor application approved.' : 'Mentor application rejected.'
        ]);
        break;

    case 'request':
        if (!canRequestMentorship($role)) {
            jsonResponse(['success' => false, 'message' => 'Only students or alumni can request mentorship.'], 403);
        }
        $mentorId = (int)($data['mentor_id'] ?? 0);
        $message = trim((string)($data['message'] ?? 'I want to join under your mentorship.'));
        if ($mentorId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Missing mentor_id.'], 400);
        }
        if ($mentorId === $userId) {
            jsonResponse(['success' => false, 'message' => 'You cannot request mentorship from yourself.'], 400);
        }

        $activeMatch = getCurrentActiveMatch($db, $userId);
        if ($activeMatch && (int)$activeMatch['mentor_id'] !== $mentorId) {
            jsonResponse([
                'success' => false,
                'message' => 'Leave your current mentor first before requesting a new one.',
                'data' => ['active_match' => $activeMatch]
            ], 409);
        }
        if ($activeMatch && (int)$activeMatch['mentor_id'] === $mentorId) {
            jsonResponse([
                'success' => true,
                'message' => 'You are already under this mentor.',
                'data' => ['group_id' => (int)($activeMatch['group_id'] ?? 0)]
            ]);
        }

        $check = $db->prepare("
            SELECT g.group_id
            FROM mentorship_profiles mp
            JOIN users u ON u.user_id = mp.mentor_user_id
            LEFT JOIN mentorship_groups g ON g.mentor_user_id = mp.mentor_user_id
            WHERE mp.mentor_user_id = :mid
              AND mp.is_active = TRUE
              AND mp.approval_status = 'approved'
              AND u.role IN ('faculty', 'alumni', 'admin')
              AND u.status = 'active'
            LIMIT 1
        ");
        $check->execute([':mid' => $mentorId]);
        $groupId = (int)($check->fetchColumn() ?: 0);
        if ($groupId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Selected mentor is not available.'], 404);
        }

        $activeBan = getActiveGroupBan($db, $groupId, $userId);
        if ($activeBan) {
            jsonResponse(['success' => false, 'message' => 'You cannot join this mentor group right now.'], 403);
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
            SELECT r.request_id, r.mentee_id, r.mentor_id, u.role AS mentee_role
            FROM mentorship_requests r
            JOIN users u ON u.user_id = r.mentee_id
            WHERE r.request_id = :rid AND r.mentor_id = :mid
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
        if (!in_array(strtolower((string)$requestRow['mentee_role']), ['student', 'alumni'], true)) {
            jsonResponse(['success' => false, 'message' => 'Only student or alumni mentees are supported.'], 400);
        }

        $groupId = null;
        if ($status === 'accepted') {
            $currentMatch = getCurrentActiveMatch($db, (int)$requestRow['mentee_id']);
            if ($currentMatch && (int)$currentMatch['mentor_id'] !== $userId) {
                jsonResponse(['success' => false, 'message' => 'This user is already under another mentor. They must leave first.'], 409);
            }

            $groupId = ensureMentorGroup($db, $userId);
            $activeBan = getActiveGroupBan($db, $groupId, (int)$requestRow['mentee_id']);
            if ($activeBan) {
                jsonResponse(['success' => false, 'message' => 'This user is banned from the mentor group right now.'], 403);
            }

            $memberStmt = $db->prepare("
                INSERT INTO mentorship_group_members (group_id, user_id, member_role)
                VALUES (:group_id, :user_id, 'member')
                ON CONFLICT (group_id, user_id) DO UPDATE SET member_role = 'member'
            ");
            $memberStmt->execute([
                ':group_id' => $groupId,
                ':user_id' => (int)$requestRow['mentee_id']
            ]);

            if (!$currentMatch) {
                $matchStmt = $db->prepare("
                    INSERT INTO mentorship_matches (mentor_id, mentee_id, group_id, status, joined_at)
                    VALUES (:mentor_id, :mentee_id, :group_id, 'active', NOW())
                ");
                $matchStmt->execute([
                    ':mentor_id' => $userId,
                    ':mentee_id' => (int)$requestRow['mentee_id'],
                    ':group_id' => $groupId
                ]);
            }

            $db->prepare("
                UPDATE mentorship_requests
                SET status = 'rejected', updated_at = NOW()
                WHERE mentee_id = :mentee_id
                  AND mentor_id <> :mentor_id
                  AND status = 'pending'
            ")->execute([
                ':mentee_id' => (int)$requestRow['mentee_id'],
                ':mentor_id' => $userId
            ]);
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

        jsonResponse([
            'success' => true,
            'message' => 'Request updated.',
            'data' => ['group_id' => $groupId]
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
                u.role AS mentee_role,
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
        $rows = array_map(function (array $row) use ($db): array {
            $row['mentee_avatar'] = resolve_profile_media_url($db, (int)$row['mentee_id'], $row['mentee_avatar'] ?? '', 'profile_picture_url', 'profile_avatar');
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        jsonResponse(['success' => true, 'data' => $rows]);
        break;

    case 'list_my_requests':
        if (!canRequestMentorship($role)) {
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

    case 'list_active_matches':
        if (in_array($role, ['faculty', 'alumni', 'admin'], true)) {
            $stmt = $db->prepare("
                SELECT
                    mm.match_id,
                    mm.mentee_id,
                    mm.group_id,
                    mm.joined_at,
                    COALESCE(p.full_name, u.email) AS mentee_name,
                    u.role AS mentee_role,
                    COALESCE(p.profile_picture_url, '') AS mentee_avatar,
                    CASE WHEN gb.user_id IS NULL THEN FALSE ELSE TRUE END AS is_banned,
                    gb.banned_until,
                    gb.is_permanent
                FROM mentorship_matches mm
                JOIN users u ON u.user_id = mm.mentee_id
                LEFT JOIN profiles p ON p.user_id = u.user_id
                LEFT JOIN mentorship_group_bans gb
                  ON gb.group_id = mm.group_id
                 AND gb.user_id = mm.mentee_id
                 AND (gb.is_permanent = TRUE OR gb.banned_until > NOW())
                WHERE mm.mentor_id = :uid
                  AND mm.status = 'active'
                ORDER BY mm.joined_at ASC
            ");
            $stmt->execute([':uid' => $userId]);
            $rows = array_map(function (array $row) use ($db): array {
                $row['mentee_avatar'] = resolve_profile_media_url($db, (int)$row['mentee_id'], $row['mentee_avatar'] ?? '', 'profile_picture_url', 'profile_avatar');
                return $row;
            }, $stmt->fetchAll(PDO::FETCH_ASSOC));
            jsonResponse(['success' => true, 'data' => $rows]);
        }

        if (canRequestMentorship($role)) {
            $currentMatch = getCurrentActiveMatch($db, $userId);
            jsonResponse(['success' => true, 'data' => $currentMatch ? [$currentMatch] : []]);
        }

        jsonResponse(['success' => true, 'data' => []]);
        break;

    case 'leave_current':
        if (!canRequestMentorship($role)) {
            jsonResponse(['success' => false, 'message' => 'Only students or alumni can leave a mentorship.'], 403);
        }
        $currentMatch = getCurrentActiveMatch($db, $userId);
        if (!$currentMatch) {
            jsonResponse(['success' => false, 'message' => 'You are not under any mentor right now.'], 404);
        }

        $db->prepare("
            UPDATE mentorship_matches
            SET status = 'left', ended_at = NOW(), ended_by = :uid, ended_reason = 'left_current_mentor'
            WHERE match_id = :match_id
        ")->execute([
            ':uid' => $userId,
            ':match_id' => (int)$currentMatch['match_id']
        ]);
        $db->prepare("DELETE FROM mentorship_group_members WHERE group_id = :gid AND user_id = :uid")
            ->execute([
                ':gid' => (int)($currentMatch['group_id'] ?? 0),
                ':uid' => $userId
            ]);

        jsonResponse(['success' => true, 'message' => 'You left your current mentor group.']);
        break;

    case 'transfer_group_admin':
        $groupId = (int)($data['group_id'] ?? 0);
        $newAdminUserId = (int)($data['new_admin_user_id'] ?? 0);
        if ($groupId <= 0 || $newAdminUserId <= 0) {
            jsonResponse(['success' => false, 'message' => 'group_id and new_admin_user_id are required.'], 400);
        }
        if (!userCanManageMentorGroup($db, $groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Only group admins can transfer admin rights.'], 403);
        }
        $memberCheck = $db->prepare("SELECT 1 FROM mentorship_group_members WHERE group_id = :gid AND user_id = :uid LIMIT 1");
        $memberCheck->execute([':gid' => $groupId, ':uid' => $newAdminUserId]);
        if (!$memberCheck->fetchColumn()) {
            jsonResponse(['success' => false, 'message' => 'New admin must be an active group member.'], 400);
        }
        transferMentorGroupAdmin($db, $groupId, $newAdminUserId);
        jsonResponse(['success' => true, 'message' => 'Group admin transferred.']);
        break;

    case 'leave_group':
        $groupId = (int)($data['group_id'] ?? 0);
        if ($groupId <= 0) {
            jsonResponse(['success' => false, 'message' => 'group_id is required.'], 400);
        }
        $memberCheck = $db->prepare("SELECT member_role FROM mentorship_group_members WHERE group_id = :gid AND user_id = :uid LIMIT 1");
        $memberCheck->execute([':gid' => $groupId, ':uid' => $userId]);
        $memberRole = $memberCheck->fetchColumn();
        if (!$memberRole) {
            jsonResponse(['success' => false, 'message' => 'You are not a member of this group.'], 404);
        }

        $groupStmt = $db->prepare("SELECT admin_user_id FROM mentorship_groups WHERE group_id = :gid LIMIT 1");
        $groupStmt->execute([':gid' => $groupId]);
        $adminUserId = (int)($groupStmt->fetchColumn() ?: 0);
        if ($adminUserId === $userId) {
            $targetUserId = (int)($data['new_admin_user_id'] ?? 0);
            if ($targetUserId <= 0) {
                $candidate = getNextGroupAdminCandidate($db, $groupId, $userId);
                $targetUserId = (int)($candidate['user_id'] ?? 0);
            }
            if ($targetUserId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Transfer admin rights to another member before leaving this group.'], 409);
            }
            transferMentorGroupAdmin($db, $groupId, $targetUserId);
        }

        $db->prepare("DELETE FROM mentorship_group_members WHERE group_id = :gid AND user_id = :uid")
            ->execute([':gid' => $groupId, ':uid' => $userId]);
        $db->prepare("
            UPDATE mentorship_matches
            SET status = 'left', ended_at = NOW(), ended_by = :uid, ended_reason = 'left_group'
            WHERE group_id = :gid
              AND mentee_id = :uid
              AND status = 'active'
        ")->execute([
            ':gid' => $groupId,
            ':uid' => $userId
        ]);

        jsonResponse(['success' => true, 'message' => 'You left the mentor group.']);
        break;

    case 'moderate_member':
        $groupId = (int)($data['group_id'] ?? 0);
        $memberUserId = (int)($data['member_user_id'] ?? 0);
        $moderationAction = strtolower(trim((string)($data['moderation_action'] ?? '')));
        $banDays = max(1, min(365, (int)($data['ban_days'] ?? 7)));
        $reason = trim((string)($data['reason'] ?? ''));
        if ($groupId <= 0 || $memberUserId <= 0 || !in_array($moderationAction, ['kick', 'ban', 'unban'], true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid moderation request.'], 400);
        }
        if ($memberUserId === $userId) {
            jsonResponse(['success' => false, 'message' => 'Use the leave action for your own account.'], 400);
        }
        if (!userCanManageMentorGroup($db, $groupId, $userId)) {
            jsonResponse(['success' => false, 'message' => 'Only the group admin can moderate members.'], 403);
        }

        $memberCheck = $db->prepare("SELECT member_role FROM mentorship_group_members WHERE group_id = :gid AND user_id = :uid LIMIT 1");
        $memberCheck->execute([':gid' => $groupId, ':uid' => $memberUserId]);
        $memberRole = (string)($memberCheck->fetchColumn() ?: '');
        if ($moderationAction !== 'unban' && $memberRole === '') {
            jsonResponse(['success' => false, 'message' => 'Selected user is not an active group member.'], 404);
        }
        if ($memberRole === 'admin') {
            jsonResponse(['success' => false, 'message' => 'Transfer admin rights before moderating this member.'], 409);
        }

        if ($moderationAction === 'unban') {
            $db->prepare("DELETE FROM mentorship_group_bans WHERE group_id = :gid AND user_id = :uid")
                ->execute([':gid' => $groupId, ':uid' => $memberUserId]);
            jsonResponse(['success' => true, 'message' => 'Group messaging restriction removed.']);
        }

        if ($moderationAction === 'ban') {
            $db->prepare("
                INSERT INTO mentorship_group_bans (group_id, user_id, banned_by, banned_until, is_permanent, reason, updated_at)
                VALUES (:gid, :uid, :banned_by, NOW() + (:ban_days || ' days')::interval, FALSE, :reason, NOW())
                ON CONFLICT (group_id, user_id)
                DO UPDATE SET
                    banned_by = EXCLUDED.banned_by,
                    banned_until = EXCLUDED.banned_until,
                    is_permanent = FALSE,
                    reason = EXCLUDED.reason,
                    updated_at = NOW()
            ")->execute([
                ':gid' => $groupId,
                ':uid' => $memberUserId,
                ':banned_by' => $userId,
                ':ban_days' => (string)$banDays,
                ':reason' => $reason ?: ('Temporarily muted for ' . $banDays . ' day(s).')
            ]);
            jsonResponse(['success' => true, 'message' => 'Member banned from group messaging.']);
        }

        $db->prepare("
            INSERT INTO mentorship_group_bans (group_id, user_id, banned_by, banned_until, is_permanent, reason, updated_at)
            VALUES (:gid, :uid, :banned_by, NULL, TRUE, :reason, NOW())
            ON CONFLICT (group_id, user_id)
            DO UPDATE SET
                banned_by = EXCLUDED.banned_by,
                banned_until = NULL,
                is_permanent = TRUE,
                reason = EXCLUDED.reason,
                updated_at = NOW()
        ")->execute([
            ':gid' => $groupId,
            ':uid' => $memberUserId,
            ':banned_by' => $userId,
            ':reason' => $reason ?: 'Removed from mentor group by admin.'
        ]);
        $db->prepare("DELETE FROM mentorship_group_members WHERE group_id = :gid AND user_id = :uid")
            ->execute([':gid' => $groupId, ':uid' => $memberUserId]);
        $db->prepare("
            UPDATE mentorship_matches
            SET status = 'removed', ended_at = NOW(), ended_by = :admin_id, ended_reason = 'kicked_from_group'
            WHERE group_id = :gid
              AND mentee_id = :uid
              AND status = 'active'
        ")->execute([
            ':gid' => $groupId,
            ':uid' => $memberUserId,
            ':admin_id' => $userId
        ]);
        jsonResponse(['success' => true, 'message' => 'Member removed from the mentor group.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}
?>
