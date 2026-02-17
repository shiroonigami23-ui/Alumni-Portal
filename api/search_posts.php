<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->validateRequest();

$term = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '';

if ($term) {
    // Search across post titles and tags (Blueprint Section 19)
    $query = "SELECT p.*, pr.full_name 
              FROM posts p 
              JOIN profiles pr ON p.user_id = pr.user_id 
              WHERE (p.title ILIKE :q OR p.post_type::text ILIKE :q OR pr.full_name ILIKE :q)
              AND p.status = 'published' 
              ORDER BY p.created_at DESC LIMIT 30";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['q' => $term]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}
?>
