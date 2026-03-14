<?php

/**
 * Search Directory - Search for Alumni, Faculty, or Students
 * Filters: role, graduation_year, company, tech_stack, location
 * Fixed: Proper column names (location_city instead of location)
 * Fixed: Tech stack from normalized user_skills table
 */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/_profile_media.php';

header('Content-Type: application/json');

// Authenticate user
try {
    $database = new Database();
    $db = $database->getConnection();

    $auth = new Auth($db);
    $user_id = $auth->validateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized: ' . $e->getMessage()]);
    exit;
}

// We start a new connection for the search query to keep it clean, or reuse $db
// The original code created a new Database instance later, let's just reuse $db from above
$pdo = $db;


// Get filters from query parameters
$role = $_GET['role'] ?? null;
$graduation_year = $_GET['year'] ?? null;
$company = $_GET['company'] ?? null;
$tech = $_GET['tech'] ?? null;
$location = $_GET['location'] ?? null;
$search = $_GET['search'] ?? null;
$branch = $_GET['branch'] ?? null;
$sort = $_GET['sort'] ?? 'name_asc';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

function branchAliases(string $branchFilter): array
{
    $normalized = strtolower(trim($branchFilter));
    if ($normalized === '') {
        return [];
    }

    $map = [
        'cs' => ['cs', 'cse', 'computer science', 'computer science & engineering', 'department of computer science & engineering'],
        'cse' => ['cs', 'cse', 'computer science', 'computer science & engineering', 'department of computer science & engineering'],
        'it' => ['it', 'information technology', 'department of information technology'],
        'ce' => ['ce', 'civil engineering', 'department of civil engineering'],
        'ee' => ['ee', 'electrical engineering', 'department of electrical engineering'],
        'ece' => ['ece', 'electronics & communication', 'electronics & communications', 'department of electronics & communications'],
        'me' => ['me', 'mechanical engineering', 'department of mechanical engineering'],
        'au' => ['au', 'automobile engineering', 'department of automobile engineering'],
    ];

    if (isset($map[$normalized])) {
        return $map[$normalized];
    }

    return [$normalized];
}

function deriveJoinedYear(?string $rollNumber): ?int
{
    if (!$rollNumber) {
        return null;
    }

    if (preg_match('/^\d{4}[A-Za-z]{2,4}(\d{2})\d+$/', strtoupper(trim($rollNumber)), $m) !== 1) {
        return null;
    }

    $yy = (int)$m[1];
    return 2000 + $yy;
}

function clear_missing_local_asset(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (stripos($path, 'data:image/') === 0) {
        return $path;
    }

    if (preg_match('/\.php(?:\?|$)/i', $path)) {
        return $path;
    }

    $normalized = str_replace('\\', '/', $path);
    if (preg_match('#^https?://#i', $normalized)) {
        $parts = parse_url($normalized);
        $candidate = isset($parts['path']) ? ltrim((string)$parts['path'], '/') : '';
        if ($candidate === '') {
            return $normalized;
        }
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        return file_exists($abs) ? $normalized : '';
    }

    $candidate = ltrim($normalized, '/');
    $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
    return file_exists($abs) ? $normalized : '';
}

try {
    // Re-using $pdo from above

    $roleStmt = $pdo->prepare("SELECT role FROM users WHERE user_id = :uid");
    $roleStmt->execute(['uid' => $user_id]);
    $currentUserRole = $roleStmt->fetchColumn();
    if (!$currentUserRole) {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized user']);
        exit;
    }

    $baseQuery = "
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        WHERE u.status = 'active'
    ";

    $where = "";
    $params = [];

    // Filter by role
    if ($role) {
        $where .= " AND u.role = :role";
        $params[':role'] = $role;
    }

    if ($graduation_year && $role === 'alumni') {
        $where .= " AND p.graduation_year = :graduation_year";
        $params[':graduation_year'] = (int)$graduation_year;
    }

    if ($branch) {
        $aliases = branchAliases((string)$branch);
        if (!empty($aliases)) {
            $branchParams = [];
            foreach ($aliases as $idx => $alias) {
                $ph = ':branch_' . $idx;
                $branchParams[] = $ph;
                $params[$ph] = strtolower($alias);
            }
            $inClause = implode(', ', $branchParams);
            $where .= " AND (
                LOWER(COALESCE(p.branch, '')) IN ($inClause)
                OR LOWER(COALESCE(p.department, '')) IN ($inClause)
            )";
        }
    }

    if ($company) {
        $where .= " AND LOWER(COALESCE(p.current_company, '')) LIKE LOWER(:company)";
        $params[':company'] = '%' . $company . '%';
    }

    if ($location) {
        $where .= " AND (LOWER(COALESCE(p.location_city, '')) LIKE LOWER(:location) OR LOWER(COALESCE(p.location_country, '')) LIKE LOWER(:location))";
        $params[':location'] = '%' . $location . '%';
    }

    if ($tech) {
        $where .= " AND EXISTS (
            SELECT 1 
            FROM user_skills us
            JOIN tech_skills ts ON us.skill_id = ts.skill_id
            WHERE us.user_id = u.user_id 
            AND LOWER(ts.skill_name) LIKE LOWER(:tech)
        )";
        $params[':tech'] = '%' . $tech . '%';
    }

    if ($search) {
        $where .= " AND (
            LOWER(COALESCE(p.full_name, '')) LIKE LOWER(:search)
            OR LOWER(COALESCE(p.bio, '')) LIKE LOWER(:search)
            OR LOWER(COALESCE(p.current_company, '')) LIKE LOWER(:search)
            OR LOWER(COALESCE(p.location_city, '')) LIKE LOWER(:search)
            OR LOWER(COALESCE(p.department, '')) LIKE LOWER(:search)
        )";
        $params[':search'] = '%' . $search . '%';
    }

    if ($currentUserRole !== 'admin') {
        $where .= " AND COALESCE(p.is_private, false) = FALSE";
    }

    $orderBy = " ORDER BY p.full_name ASC";
    if ($sort === 'name_desc') {
        $orderBy = " ORDER BY p.full_name DESC";
    } elseif ($sort === 'year_desc') {
        $orderBy = " ORDER BY p.graduation_year DESC NULLS LAST, p.full_name ASC";
    } elseif ($sort === 'year_asc') {
        $orderBy = " ORDER BY p.graduation_year ASC NULLS LAST, p.full_name ASC";
    }

    $query = "
        SELECT DISTINCT
            u.user_id,
            u.role,
            p.full_name,
            p.graduation_year,
            p.current_company,
            p.job_role,
            p.location_city,
            p.location_country,
            p.branch,
            p.department,
            p.roll_number,
            p.designation,
            p.profile_picture_url,
            p.bio,
            p.linkedin_url,
            p.personal_website,
            COALESCE(
                (
                    SELECT STRING_AGG(ts.skill_name, ', ')
                    FROM user_skills us
                    JOIN tech_skills ts ON us.skill_id = ts.skill_id
                    WHERE us.user_id = u.user_id
                ), 
                ''
            ) AS tech_stack
        $baseQuery
        $where
        $orderBy
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($query);
    $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT u.user_id) $baseQuery $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $mapped = array_map(static function (array $row) use ($db): array {
        $location = trim(implode(', ', array_filter([$row['location_city'] ?? '', $row['location_country'] ?? ''])));
        $skills = array_values(array_filter(array_map('trim', explode(',', (string)($row['tech_stack'] ?? '')))));
        $role = (string)($row['role'] ?? '');
        $graduationYear = $row['graduation_year'] ? (int)$row['graduation_year'] : null;
        $joinedYear = null;
        if ($role === 'student') {
            $joinedYear = deriveJoinedYear($row['roll_number'] ?? null);
        }

        $yearDisplay = null;
        if ($role === 'student' && $joinedYear) {
            $yearDisplay = "Joined in " . $joinedYear;
        } elseif ($role === 'alumni' && $graduationYear) {
            $startYear = $graduationYear - 4;
            $yearDisplay = $startYear . "-" . $graduationYear;
        } elseif ($graduationYear) {
            $yearDisplay = (string)$graduationYear;
        }

        return [
            'id' => (int)$row['user_id'],
            'role' => $role,
            'name' => (string)($row['full_name'] ?: 'RJIT Member'),
            'graduation_year' => $graduationYear,
            'joined_year' => $joinedYear,
            'year_display' => $yearDisplay,
            'current_company' => $row['current_company'] ?? null,
            'current_position' => $row['job_role'] ?? $row['designation'] ?? null,
            'location' => $location ?: null,
            'branch' => $row['branch'] ?? null,
            'department' => $row['department'] ?? null,
            'roll_number' => $row['roll_number'] ?? null,
            'avatar' => resolve_profile_media_url($db, (int)$row['user_id'], $row['profile_picture_url'] ? str_replace('\\', '/', (string)$row['profile_picture_url']) : null, 'profile_picture_url', 'profile_avatar') ?: null,
            'bio' => $row['bio'] ?? null,
            'skills' => $skills,
            'is_private' => false
        ];
    }, $results);

    $topCompaniesStmt = $pdo->prepare("
        SELECT p.current_company AS company, COUNT(*)::int AS count
        FROM users u
        JOIN profiles p ON p.user_id = u.user_id
        WHERE u.status = 'active'
          AND COALESCE(p.current_company, '') <> ''
        GROUP BY p.current_company
        ORDER BY COUNT(*) DESC, p.current_company ASC
        LIMIT 6
    ");
    $topCompaniesStmt->execute();
    $topCompanies = array_map(static function (array $r): array {
        return ['name' => (string)$r['company'], 'count' => (int)$r['count']];
    }, $topCompaniesStmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'total' => $total,
        'page' => $page,
        'per_page' => $limit,
        'data' => $mapped,
        'top_companies' => $topCompanies,
        // Legacy keys for compatibility
        'Count' => $total,
        'value' => $results
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
