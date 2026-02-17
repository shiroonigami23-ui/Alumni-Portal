<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get search parameters
$skill = isset($_GET['skill']) ? $_GET['skill'] : null;
$branch = isset($_GET['branch']) ? $_GET['branch'] : null;
$city = isset($_GET['city']) ? $_GET['city'] : null;

$query = "SELECT u.user_id, u.email, p.full_name, p.tech_stack, p.branch, p.location_city, p.profile_picture_url 
          FROM users u 
          JOIN profiles p ON u.user_id = p.user_id 
          WHERE u.role = 'alumni' AND u.status = 'active'";

$params = [];

if ($skill) {
    $query .= " AND p.tech_stack ILIKE :skill"; // Case-insensitive partial match
    $params['skill'] = '%' . $skill . '%';
}
if ($branch) {
    $query .= " AND p.branch = :branch";
    $params['branch'] = $branch;
}
if ($city) {
    $query .= " AND p.location_city ILIKE :city";
    $params['city'] = '%' . $city . '%';
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["count" => count($results), "alumni" => $results]);
?>