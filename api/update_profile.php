<?php
/**
 * Update your profile.
 * POST JSON with any subset of the fields below. Every field is validated; problems come back as
 * 422 { message, errors: { field: reason } } instead of a database error.
 *
 * Students: identity fields (name, branch, course, batch years) come from institute records and stay
 * locked; everything else (bio, skills, links, city, photo, pronouns...) is theirs to edit.
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
require_once __DIR__ . '/../helpers/Cache.php';
register_shutdown_function(function () { if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && http_response_code() < 400) { Cache::bump('atlas'); } });
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
require_once __DIR__ . '/_privacy.php';

$db = (new Database())->getConnection();
if (!$db) {
    http_response_code(503);
    echo json_encode(["success" => false, "status" => "error", "message" => "Database unavailable."]);
    exit;
}
$auth = new Auth($db);

function profile_out(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body + ['success' => $code < 400, 'status' => $code < 400 ? 'success' : 'error']);
    exit;
}

// field => [kind, max length]
const PROFILE_FIELDS = [
    'full_name' => ['name', 80],
    'bio' => ['text', 600],
    'skills' => ['text', 600],
    'tech_stack' => ['text', 600],
    'profile_picture_url' => ['media', 600],
    'cover_photo_url' => ['media', 600],
    'current_company' => ['line', 120],
    'job_role' => ['line', 120],
    'department' => ['line', 150],
    'branch' => ['line', 150],
    'designation' => ['line', 150],
    'specialization' => ['line', 200],
    'office_location' => ['line', 200],
    'course' => ['line', 100],
    'graduation_year' => ['year', 0],
    'joining_year' => ['year', 0],
    'contact_number' => ['phone', 20],
    'help_alumni_mates' => ['text', 600],
    'location_city' => ['line', 120],
    'location_country' => ['line', 120],
    'pronouns' => ['line', 40],
    'personal_website' => ['url', 255],
    'linkedin_url' => ['url', 255],
    'github_url' => ['url', 255],
    'twitter_url' => ['url', 255],
];
const STUDENT_LOCKED = ['full_name', 'branch', 'course', 'graduation_year', 'joining_year', 'department', 'designation'];
const FIELD_LABELS = [
    'full_name' => 'Name', 'bio' => 'Headline', 'location_city' => 'City', 'location_country' => 'Country',
    'current_company' => 'Company', 'job_role' => 'Role', 'contact_number' => 'Phone', 'personal_website' => 'Website',
    'linkedin_url' => 'LinkedIn', 'github_url' => 'GitHub', 'twitter_url' => 'X / Twitter', 'graduation_year' => 'Graduation year',
    'joining_year' => 'Joining year', 'profile_picture_url' => 'Photo', 'cover_photo_url' => 'Cover photo',
];

/** Returns [value, error|null]. */
function clean_profile_value(string $kind, int $max, $raw): array
{
    if ($raw === null) {
        return [null, null];
    }
    if (is_array($raw) || is_object($raw)) {
        return [null, 'must be text'];
    }
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$raw));
    switch ($kind) {
        case 'year':
            if ($v === '') {
                return [null, null];
            }
            $y = (int)$v;
            $max = (int)date('Y') + 8;
            return ($y >= 1950 && $y <= $max) ? [$y, null] : [null, "must be a year between 1950 and $max"];
        case 'phone':
            if ($v === '') {
                return ['', null];
            }
            return preg_match('/^\+?[0-9][0-9 ()\-]{5,18}$/', $v) ? [$v, null] : [null, 'should look like +91 98765 43210'];
        case 'url':
            if ($v === '') {
                return ['', null];
            }
            if (!preg_match('#^[a-z][a-z0-9+.\-]*://#i', $v)) {
                $v = 'https://' . ltrim($v, '/');   // "linkedin.com/in/me" → https://linkedin.com/in/me
            }
            $scheme = strtolower((string)parse_url($v, PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https'], true) || !filter_var($v, FILTER_VALIDATE_URL) || preg_match('/[<>"\'\s]/', $v)) {
                return [null, 'must be a web link (https://…)'];
            }
            return mb_strlen($v) <= $max ? [$v, null] : [null, "is too long (max $max characters)"];
        case 'media':
            if ($v === '') {
                return ['', null];
            }
            if (preg_match('#^(https://[^\s<>"\']+|api/asset\.php\?token=[a-f0-9]+|storage/[A-Za-z0-9_./\-]+)$#', $v) && strpos($v, '..') === false) {
                return mb_strlen($v) <= $max ? [$v, null] : [null, 'link is too long'];
            }
            return [null, 'must be an uploaded image or an https:// link'];
        default: // name, line, text — plain text only
            $v = trim(strip_tags(html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $v = $kind === 'text' ? preg_replace("/\n{3,}/", "\n\n", $v) : preg_replace('/\s+/u', ' ', $v);
            if ($kind === 'name' && mb_strlen($v) < 2) {
                return [null, 'needs at least 2 characters'];
            }
            if (mb_strlen($v) > $max) {
                return [null, "is too long (max $max characters)"];
            }
            return [$v, null];
    }
}

try {
    $user_id = (int)$auth->validateRequest();
    privacy_ensure_schema($db);
    $userRole = privacy_role($db, $user_id);
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $payload = [];
    $errors = [];
    $locked = [];
    foreach (PROFILE_FIELDS as $field => [$kind, $max]) {
        if (!array_key_exists($field, $data)) {
            continue;
        }
        if ($userRole === 'student' && in_array($field, STUDENT_LOCKED, true)) {
            $locked[] = $field;
            continue;
        }
        [$value, $err] = clean_profile_value($kind, $max, $data[$field]);
        if ($err !== null) {
            $errors[$field] = (FIELD_LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field))) . ' ' . $err . '.';
            continue;
        }
        $payload[$field] = $value;
    }

    if ($errors) {
        profile_out(422, ['message' => reset($errors), 'errors' => $errors]);
    }
    if (!$payload) {
        profile_out(200, [
            'message' => $locked ? 'Name, branch and batch come from institute records and can\'t be changed here.' : 'No changes provided.',
            'locked_fields' => $locked,
        ]);
    }

    $db->prepare("INSERT INTO profiles (user_id, full_name) SELECT :uid, split_part(email, '@', 1) FROM users WHERE user_id = :uid2
                  ON CONFLICT (user_id) DO NOTHING")->execute([':uid' => $user_id, ':uid2' => $user_id]);
    $sets = [];
    $params = [':uid' => $user_id];
    foreach ($payload as $k => $v) {
        $sets[] = "$k = :$k";
        $params[":$k"] = $v;
    }
    $sets[] = "updated_at = NOW()";
    $db->prepare("UPDATE profiles SET " . implode(', ', $sets) . " WHERE user_id = :uid")->execute($params);

    profile_out(200, [
        'message' => 'Profile updated successfully.' . ($locked ? ' (Name, branch and batch are managed by the institute.)' : ''),
        'updated' => array_keys($payload),
        'locked_fields' => $locked,
    ]);
} catch (Throwable $e) {
    error_log('update_profile: ' . $e->getMessage());
    profile_out(500, ['message' => 'Failed to update profile. Please try again.']);
}
