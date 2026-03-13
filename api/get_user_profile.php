<?php
/**
 * Get User Profile API
 * Retrieve public profile information for any user
 */

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../helpers/TechStackHelper.php';

header('Content-Type: application/json');

function derive_joined_year(?string $rollNumber, ?string $email): ?int
{
    $candidates = [];
    if ($rollNumber) {
        $candidates[] = trim($rollNumber);
    }
    if ($email) {
        $local = strtolower(trim((string)explode('@', (string)$email)[0]));
        if ($local !== '') {
            $candidates[] = $local;
        }
    }

    foreach ($candidates as $value) {
        if (preg_match('/^\d{4}[a-z]{2,4}(\d{2})\d+$/i', $value, $m) === 1) {
            return 2000 + (int)$m[1];
        }
    }

    return null;
}

// Authenticate user (optional)
$database = new Database(); $db = $database->getConnection(); $auth = new Auth($db);
$current_user = $auth->validateRequest();

// Get user_id from query parameter
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['message' => 'user_id parameter required']);
    exit;
}

// Connect to database
$database = new Database();
$db = $database->getConnection();
$techHelper = new TechStackHelper($db);

try {
    // Get user profile
    $query = "SELECT u.user_id, u.email, u.role, u.status, u.created_at,
                     u.total_posts, u.total_likes_received, u.login_streak,
                     p.full_name, p.bio, p.profile_picture_url, p.cover_photo_url,
                     p.graduation_year, p.course, p.branch,
                     p.current_company, p.job_role,
                     p.department, p.designation, p.specialization, p.office_location,
                     p.location_city, p.location_country,
                     p.contact_number, p.personal_website, 
                     p.linkedin_url, p.github_url, p.twitter_url,
                     CASE WHEN u.role = 'student' THEN false ELSE COALESCE(p.is_private, false) END AS is_private,
                     p.show_email, p.show_contact,
                     p.roll_number, p.year_of_study
              FROM users u
              LEFT JOIN profiles p ON u.user_id = p.user_id
              WHERE u.user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
        exit;
    }

    $profile['posts_count'] = 0;
    $pCount = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'published'");
    $pCount->execute([':uid' => $user_id]);
    $profile['posts_count'] = (int)$pCount->fetchColumn();

    $profile['connections_count'] = 0;
    $profile['followers_count'] = 0;
    $profile['following_count'] = 0;
    $profile['is_connected'] = false;
    $connTable = $db->query("SELECT to_regclass('public.connections')")->fetchColumn();
    if ($connTable) {
        $cCount = $db->prepare("
            SELECT COUNT(*)
            FROM connections c
            WHERE c.status = 'accepted'
              AND (c.requester_user_id = :uid OR c.addressee_user_id = :uid)
        ");
        $cCount->execute([':uid' => $user_id]);
        $profile['connections_count'] = (int)$cCount->fetchColumn();

        $followersCount = $db->prepare("
            SELECT COUNT(*)
            FROM connections c
            WHERE c.status = 'accepted'
              AND c.addressee_user_id = :uid
        ");
        $followersCount->execute([':uid' => $user_id]);
        $profile['followers_count'] = (int)$followersCount->fetchColumn();

        $followingCount = $db->prepare("
            SELECT COUNT(*)
            FROM connections c
            WHERE c.status = 'accepted'
              AND c.requester_user_id = :uid
        ");
        $followingCount->execute([':uid' => $user_id]);
        $profile['following_count'] = (int)$followingCount->fetchColumn();

        if ($current_user && (int)$current_user !== (int)$user_id) {
            $cState = $db->prepare("
                SELECT 1
                FROM connections c
                WHERE c.status = 'accepted'
                  AND c.requester_user_id = :me
                  AND c.addressee_user_id = :uid
                LIMIT 1
            ");
            $cState->execute([':me' => $current_user, ':uid' => $user_id]);
            $profile['is_connected'] = $cState->fetch() ? true : false;
        }
    }
    
    // Check if profile is private: allow only self for now.
    if ($profile['is_private'] && (!$current_user || (int)$current_user !== (int)$user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This profile is private']);
        exit;
    }
    
    // Ensure core fields exist even when profile row is missing
    if (empty($profile['full_name'])) {
        $profile['full_name'] = explode('@', (string)$profile['email'])[0];
    }
    if (!empty($profile['profile_picture_url'])) {
        $profile['profile_picture_url'] = str_replace('\\', '/', (string)$profile['profile_picture_url']);
    }

    $avatar = (string)($profile['profile_picture_url'] ?? '');
    $looksPlaceholder = (
        $avatar === '' ||
        stripos($avatar, 'via.placeholder.com') !== false ||
        stripos($avatar, 'placeholder') !== false ||
        stripos($avatar, 'data:image/svg+xml') === 0
    );

    if ($looksPlaceholder && ($profile['role'] ?? '') === 'faculty') {
        $emailSlug = strtolower((string)$profile['email']);
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
                $profile['profile_picture_url'] = $candidate;
                break;
            }
        }
    }
    if (!empty($profile['cover_photo_url'])) {
        $profile['cover_photo_url'] = str_replace('\\', '/', (string)$profile['cover_photo_url']);
    }
    if (!isset($profile['is_private'])) {
        $profile['is_private'] = false;
    }
    if (!isset($profile['show_email'])) {
        $profile['show_email'] = true;
    }
    if (!isset($profile['show_contact'])) {
        $profile['show_contact'] = false;
    }
    $profile['joined_year'] = derive_joined_year($profile['roll_number'] ?? null, $profile['email'] ?? null);
    
    // Get tech stack
    if(isset($techHelper)) {
        $profile["tech_stack"] = $techHelper->getUserTechStack($user_id);
        $profile['tech_skills'] = $techHelper->getUserTechStackArray($user_id);
    } else {
        $profile["tech_stack"] = '';
        $profile['tech_skills'] = [];
    }
    
    // Get badges
    $badge_query = "SELECT badge_type, earned_at FROM badges WHERE user_id = :user_id ORDER BY earned_at DESC";
    $badge_stmt = $db->prepare($badge_query);
    $badge_stmt->execute(['user_id' => $user_id]);
    $profile['badges'] = $badge_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pinned posts
    $pinned_query = "SELECT p.post_id, p.title, p.post_type, p.content_file_path,
                            p.thumbnail_url, p.reaction_count, p.comment_count,
                            p.created_at, p.is_edited
                     FROM pinned_posts pp
                     JOIN posts p ON pp.post_id = p.post_id
                     WHERE pp.user_id = :user_id AND p.status = 'published'::post_status
                     ORDER BY pp.pin_order ASC";
    $pinned_stmt = $db->prepare($pinned_query);
    $pinned_stmt->execute(['user_id' => $user_id]);
    $profile['pinned_posts'] = $pinned_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Privacy: Hide sensitive info if needed
    if (!$profile['show_email']) {
        unset($profile['email']);
    }
    
    if (!$profile['show_contact']) {
        unset($profile['contact_number']);
    }
    
    // Check if current user has blocked this user or vice versa
    if ($current_user) {
        $block_query = "SELECT 1 FROM blocks 
                       WHERE (blocker_user_id = :current_user AND blocked_user_id = :user_id)
                          OR (blocker_user_id = :user_id AND blocked_user_id = :current_user)";
        $block_stmt = $db->prepare($block_query);
        $block_stmt->execute([
            'current_user' => $current_user,
            'user_id' => $user_id
        ]);
        $profile['is_blocked'] = $block_stmt->fetch() ? true : false;
    } else {
        $profile['is_blocked'] = false;
    }
    
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'data' => $profile
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
