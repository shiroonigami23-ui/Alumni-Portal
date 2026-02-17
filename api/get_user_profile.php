<?php
/**
 * Get User Profile API
 * Retrieve public profile information for any user
 */

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once '../helpers/TechStackHelper.php';

header('Content-Type: application/json');

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
                     p.is_private, p.show_email, p.show_contact,
                     p.roll_number, p.year_of_study
              FROM users u
              JOIN profiles p ON u.user_id = p.user_id
              WHERE u.user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
        exit;
    }
    
    // Check if profile is private
    if ($profile['is_private']) {
        // Only admin can view private profiles
        if (!$current_user || null !== 'admin') {
            // Unless it's the user's own profile
            if (!$current_user || $current_user != $user_id) {
                http_response_code(403);
                echo json_encode(['message' => 'This profile is private']);
                exit;
            }
        }
    }
    
    // Get tech stack
    if(isset($techHelper)) $profile["tech_stack"] = $techHelper->getUserTechStack($user_id);
    $profile['tech_skills'] = $techHelper->getUserTechStackArray($user_id);
    
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
    
    echo json_encode($profile);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
