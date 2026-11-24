<?php
/**
 * Get User Profile API
 * Retrieve public profile information for any user
 */

require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
require_once '../config/Database.php';
require_once '../config/DbCompat.php';
require_once '../middleware/Auth.php';
require_once '../helpers/TechStackHelper.php';
require_once __DIR__ . '/_content_store.php';
require_once __DIR__ . '/_profile_media.php';
require_once __DIR__ . '/_privacy.php';
require_once __DIR__ . '/_usernames.php';

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
$database = new Database(); $db = $database->getConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database unavailable']);
    exit;
}
$auth = new Auth($db);
$current_user = (int)$auth->validateRequest();
privacy_ensure_schema($db);
$current_user_role = null;
if ($current_user) {
    $current_user_role = $api->role;   // already loaded by the API kernel
}

// Get user_id from query parameter
$user_id = (int)($_GET['user_id'] ?? 0);
// Profiles can also be opened by @handle (current, or one changed in the last 30 days).
$redirectedFrom = null;
if ($user_id <= 0 && isset($_GET['username'])) {
    ensure_username_schema($db);
    $hit = username_resolve($db, (string)$_GET['username']);
    if ($hit) {
        $user_id = $hit['user_id'];
        $redirectedFrom = $hit['redirected'] ? username_normalise((string)$_GET['username']) : null;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No one has that handle.']);
        exit;
    }
}

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'user_id parameter required']);
    exit;
}


try {
    // Get user profile
    $query = "SELECT u.user_id, u.username, u.email, u.role, u.status, u.created_at,
                     u.total_posts, u.total_likes_received, u.login_streak,
                     p.full_name, p.bio, p.profile_picture_url, p.cover_photo_url,
                     p.graduation_year, p.joining_year, p.course, p.branch,
                     p.current_company, p.job_role,
                     p.department, p.designation, p.specialization, p.office_location,
                     p.location_city, p.location_country,
                     p.contact_number, p.personal_website, 
                     p.linkedin_url, p.github_url, p.twitter_url,
                     p.help_alumni_mates,
                     CASE WHEN u.role = 'student' THEN false ELSE COALESCE(p.is_private, false) END AS is_private,
                     COALESCE(p.show_email, TRUE) AS show_email, COALESCE(p.show_contact, FALSE) AS show_contact,
                     COALESCE(p.open_to_mentor, FALSE) AS open_to_mentor, COALESCE(p.open_to_refer, FALSE) AS open_to_refer,
                     COALESCE(p.open_to_work, FALSE) AS open_to_work, p.pronouns,
                     p.roll_number, p.year_of_study,
                     c.posts_count, c.connections_count, c.followers_count, c.following_count,
                     (SELECT status FROM connections WHERE requester_user_id = :me AND addressee_user_id = u.user_id LIMIT 1) AS viewer_follow_status,
                     EXISTS (SELECT 1 FROM blocks b
                             WHERE (b.blocker_user_id = :me2 AND b.blocked_user_id = u.user_id)
                                OR (b.blocker_user_id = u.user_id AND b.blocked_user_id = :me3)) AS pair_blocked
              FROM users u
              LEFT JOIN profiles p ON u.user_id = p.user_id
              -- every counter in the same round trip
              LEFT JOIN LATERAL (
                  SELECT
                      (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id AND status = 'published') AS posts_count,
                      COUNT(*) AS connections_count,
                      COUNT(*) FILTER (WHERE cc.addressee_user_id = u.user_id) AS followers_count,
                      COUNT(*) FILTER (WHERE cc.requester_user_id = u.user_id) AS following_count
                  FROM connections cc
                  WHERE cc.status = 'accepted' AND (cc.requester_user_id = u.user_id OR cc.addressee_user_id = u.user_id)
              ) c ON TRUE
              WHERE u.user_id = :user_id";
    
    // Profile, counters, follow state and block state: one query (was five).
    $stmt = $db->prepare($query);
    $stmt->execute(['user_id' => $user_id, 'me' => (int)$current_user, 'me2' => (int)$current_user, 'me3' => (int)$current_user]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profile && $current_user && (int)$current_user !== $user_id) {
        // Seed the shared privacy memos so the checks below don't query again.
        $pairKey = min((int)$current_user, $user_id) . ':' . max((int)$current_user, $user_id);
        $GLOBALS['__privacy_blocked'][$pairKey] = filter_var($profile['pair_blocked'], FILTER_VALIDATE_BOOLEAN);
        $fs = (string)($profile['viewer_follow_status'] ?? '');
        $GLOBALS['__privacy_follow'][(int)$current_user . ':' . $user_id] = $fs === 'accepted' ? 'following' : ($fs === 'pending' ? 'requested' : 'none');
        $GLOBALS['__privacy_private'][$user_id] = strtolower((string)$profile['role']) !== 'student'
            && filter_var($profile['is_private'], FILTER_VALIDATE_BOOLEAN);
    }
    
    $viewerRole = strtolower((string)$current_user_role);
    $isSelf = $current_user === $user_id;
    $isAdmin = $viewerRole === 'admin';
    // Blocked either way: behave as if the profile doesn't exist.
    if ($profile && !$isSelf && !$isAdmin && privacy_is_blocked($db, $current_user, $user_id)) {
        $profile = false;
    }
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
        exit;
    }

    $c = $profile;
    $profile['posts_count'] = (int)($c['posts_count'] ?? 0);
    $profile['connections_count'] = (int)($c['connections_count'] ?? 0);
    $profile['followers_count'] = (int)($c['followers_count'] ?? 0);
    $profile['following_count'] = (int)($c['following_count'] ?? 0);
    $profile['is_connected'] = $current_user && (int)$current_user !== (int)$user_id && ($profile['viewer_follow_status'] ?? '') === 'accepted';
    $pairBlocked = filter_var($profile['pair_blocked'] ?? false, FILTER_VALIDATE_BOOLEAN);
    unset($profile['viewer_follow_status'], $profile['pair_blocked']);
    
    foreach (['is_private', 'show_email', 'show_contact', 'open_to_mentor', 'open_to_refer', 'open_to_work', 'help_alumni_mates'] as $flag) {
        $profile[$flag] = filter_var($profile[$flag] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
    $profile['user_id'] = (int)$profile['user_id'];
    $profile['follow_state'] = $current_user ? privacy_follow_state($db, $current_user, $user_id) : 'none';
    $canView = privacy_can_view($db, $current_user, $viewerRole, $user_id);
    $profile['is_locked'] = !$canView;
    $profile['can_message'] = !$isSelf;  // anyone may DM (blocked pairs never reach here)

    // Who viewed your profile: one row per viewer per day (admins browsing don't count).
    if ($current_user && !$isSelf && !$isAdmin) {
        try {
            $db->prepare("INSERT INTO profile_views (viewer_user_id, viewed_user_id) VALUES (:v, :o)
                          ON CONFLICT (viewer_user_id, viewed_user_id, view_day) DO UPDATE SET viewed_at = NOW()")
               ->execute([':v' => $current_user, ':o' => $user_id]);
        } catch (Throwable $e) {
        }
    }

    if (!$canView) {
        // Locked card: enough to recognise the person and ask to follow — nothing else.
        $keep = ['user_id', 'username', 'role', 'full_name', 'profile_picture_url', 'branch', 'course', 'graduation_year', 'joining_year',
                 'current_company', 'designation', 'department', 'is_private', 'pronouns', 'created_at',
                 'posts_count', 'followers_count', 'following_count', 'connections_count', 'is_connected', 'follow_state', 'is_locked', 'can_message'];
        $locked = array_intersect_key($profile, array_flip($keep));
        if (empty($locked['full_name'])) {
            $locked['full_name'] = 'RJIT member';
        }
        $locked['profile_picture_url'] = resolve_profile_media_url($db, (int)$user_id, $locked['profile_picture_url'] ?? '', 'profile_picture_url', 'profile_avatar');
        $locked['bio'] = '';
        $locked['badges'] = [];
        $locked['pinned_posts'] = [];
        $locked['tech_skills'] = [];
        $locked['is_blocked'] = false;
        echo json_encode(['success' => true, 'status' => 'success', 'data' => $locked]);
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
    $profile['profile_picture_url'] = resolve_profile_media_url($db, (int)$user_id, $profile['profile_picture_url'] ?? '', 'profile_picture_url', 'profile_avatar');
    if (!empty($profile['cover_photo_url'])) {
        $profile['cover_photo_url'] = str_replace('\\', '/', (string)$profile['cover_photo_url']);
    }
    $profile['cover_photo_url'] = resolve_profile_media_url($db, (int)$user_id, $profile['cover_photo_url'] ?? '', 'cover_photo_url', 'profile_cover');
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
    // Skills, badges and pinned posts in one round trip (was three), each as a JSON array.
    $extra = $db->prepare("
        SELECT
            (SELECT COALESCE(json_agg(us.skill_name ORDER BY us.skill_name), '[]'::json)
               FROM user_tech_stats us WHERE us.user_id = :u1) AS skills,
            (SELECT COALESCE(json_agg(json_build_object('badge_type', b.badge_type, 'earned_at', b.earned_at::text) ORDER BY b.earned_at DESC), '[]'::json)
               FROM badges b WHERE b.user_id = :u2) AS badges,
            (SELECT COALESCE(json_agg(json_build_object(
                        'post_id', p.post_id, 'title', p.title, 'post_type', p.post_type, 'content_file_path', p.content_file_path,
                        'thumbnail_url', p.thumbnail_url, 'reaction_count', p.reaction_count, 'comment_count', p.comment_count,
                        'created_at', p.created_at::text, 'is_edited', p.is_edited) ORDER BY pp.pin_order), '[]'::json)
               FROM pinned_posts pp JOIN posts p ON pp.post_id = p.post_id
              WHERE pp.user_id = :u3 AND p.status = 'published') AS pinned
    ");
    $extra->execute(['u1' => $user_id, 'u2' => $user_id, 'u3' => $user_id]);
    $ex = $extra->fetch(PDO::FETCH_ASSOC) ?: [];
    $profile['tech_skills'] = json_decode((string)($ex['skills'] ?? '[]'), true) ?: [];
    $profile['tech_stack'] = $profile['tech_skills'] ? implode(', ', $profile['tech_skills']) : null;
    $profile['badges'] = json_decode((string)($ex['badges'] ?? '[]'), true) ?: [];
    $pinned = json_decode((string)($ex['pinned'] ?? '[]'), true) ?: [];
    prefetch_content_payloads($db, array_column($pinned, 'content_file_path'));
    $profile['pinned_posts'] = array_map(function (array $post) use ($db) {
        $payload = load_content_payload($db, (string)($post['content_file_path'] ?? ''));
        $post['content'] = $payload['content'];
        $post['attachments'] = $payload['attachments'];
        return $post;
    }, $pinned);
    
    // Contact details: owner decides; self and admins always see them.
    if (!$isSelf && !$isAdmin) {
        if (!$profile['show_email']) {
            unset($profile['email']);
        }
        if (!$profile['show_contact']) {
            unset($profile['contact_number']);
        }
        if ($viewerRole !== 'faculty') {
            unset($profile['roll_number']);
        }
    }

    if ($isSelf) {
        // Owner-only extras: profile strength, follow requests, recent viewers.
        $checks = [
            'photo' => !empty($profile['profile_picture_url']) && stripos((string)$profile['profile_picture_url'], 'data:image/svg') !== 0,
            'cover' => !empty($profile['cover_photo_url']),
            'headline' => trim((string)($profile['bio'] ?? '')) !== '',
            'branch' => trim((string)($profile['branch'] ?? '')) !== '',
            'skills' => !empty($profile['tech_skills']),
            'location' => trim((string)($profile['location_city'] ?? '')) !== '',
            'links' => trim((string)($profile['linkedin_url'] ?? '') . ($profile['github_url'] ?? '') . ($profile['personal_website'] ?? '')) !== '',
        ];
        if (($profile['role'] ?? '') === 'alumni') {
            $checks['work'] = trim((string)($profile['current_company'] ?? '')) !== '';
        }
        $done = count(array_filter($checks));
        $profile['profile_strength'] = [
            'percent' => (int)round(100 * $done / max(1, count($checks))),
            'missing' => array_keys(array_filter($checks, fn ($v) => !$v)),
        ];
        $rq = $db->prepare("SELECT COUNT(*) FROM connections WHERE addressee_user_id = :u AND status = 'pending'");
        $rq->execute([':u' => $user_id]);
        $profile['follow_requests_count'] = (int)$rq->fetchColumn();
        $pv = $db->prepare("
            SELECT v.viewer_user_id AS user_id, MAX(v.viewed_at) AS viewed_at,
                   COALESCE(NULLIF(TRIM(MAX(p.full_name)), ''), 'RJIT member') AS full_name, MAX(u.role) AS role,
                   MAX(p.current_company) AS current_company, MAX(p.profile_picture_url) AS profile_picture_url
            FROM profile_views v
            JOIN users u ON u.user_id = v.viewer_user_id
            LEFT JOIN profiles p ON p.user_id = v.viewer_user_id
            WHERE v.viewed_user_id = :u AND v.viewed_at > NOW() - INTERVAL '30 days'
            GROUP BY v.viewer_user_id
            ORDER BY MAX(v.viewed_at) DESC
            LIMIT 8
        ");
        $pv->execute([':u' => $user_id]);
        $viewers = $pv->fetchAll(PDO::FETCH_ASSOC);
        foreach ($viewers as &$v) {
            $v['user_id'] = (int)$v['user_id'];
            $v['profile_picture_url'] = resolve_profile_media_url($db, $v['user_id'], $v['profile_picture_url'] ?? '', 'profile_picture_url', 'profile_avatar');
        }
        unset($v);
        $pc = $db->prepare("SELECT COUNT(DISTINCT viewer_user_id) FROM profile_views WHERE viewed_user_id = :u AND viewed_at > NOW() - INTERVAL '30 days'");
        $pc->execute([':u' => $user_id]);
        $profile['profile_views_30d'] = (int)$pc->fetchColumn();
        $profile['recent_viewers'] = $viewers;
    }
    
    // Blocked either way (already known from the profile query)
    $profile['is_blocked'] = $current_user ? $pairBlocked : false;
    
    $profile['redirected_from'] = $redirectedFrom;
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'data' => $profile
    ]);
    
} catch (Throwable $e) {
    error_log('get_user_profile: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Could not load this profile.'
    ]);
}
