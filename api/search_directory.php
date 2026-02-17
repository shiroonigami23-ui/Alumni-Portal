<?php

/**
 * Search Directory - Search for Alumni, Faculty, or Students
 * Filters: role, graduation_year, company, tech_stack, location
 * Fixed: Proper column names (location_city instead of location)
 * Fixed: Tech stack from normalized user_skills table
 */

require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';

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
$search = $_GET['search'] ?? null; // General search term

try {
    // Re-using $pdo from above


    // Base query - join users, profiles, and aggregate tech skills
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
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        WHERE u.status = 'active'
    ";

    $params = [];

    // Filter by role
    if ($role) {
        $query .= " AND u.role = :role";
        $params[':role'] = $role;
    }

    // Filter by graduation year
    if ($graduation_year) {
        $query .= " AND p.graduation_year = :graduation_year";
        $params[':graduation_year'] = (int)$graduation_year;
    }

    // Filter by company
    if ($company) {
        $query .= " AND LOWER(p.current_company) LIKE LOWER(:company)";
        $params[':company'] = '%' . $company . '%';
    }

    // Filter by location
    if ($location) {
        $query .= " AND (LOWER(p.location_city) LIKE LOWER(:location) OR LOWER(p.location_country) LIKE LOWER(:location))";
        $params[':location'] = '%' . $location . '%';
    }

    // Filter by tech stack (search in user_skills)
    if ($tech) {
        $query .= " AND EXISTS (
            SELECT 1 
            FROM user_skills us
            JOIN tech_skills ts ON us.skill_id = ts.skill_id
            WHERE us.user_id = u.user_id 
            AND LOWER(ts.skill_name) LIKE LOWER(:tech)
        )";
        $params[':tech'] = '%' . $tech . '%';
    }

    // General search (name, bio, company, location)
    if ($search) {
        $query .= " AND (
            LOWER(p.full_name) LIKE LOWER(:search)
            OR LOWER(p.bio) LIKE LOWER(:search)
            OR LOWER(p.current_company) LIKE LOWER(:search)
            OR LOWER(p.location_city) LIKE LOWER(:search)
            OR LOWER(p.department) LIKE LOWER(:search)
        )";
        $params[':search'] = '%' . $search . '%';
    }

    // Exclude private profiles (unless admin)
    if ($user['role'] !== 'admin') {
        $query .= " AND p.is_private = FALSE";
    }

    // Order by name
    $query .= " ORDER BY p.full_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'Count' => count($results),
        'value' => $results
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
