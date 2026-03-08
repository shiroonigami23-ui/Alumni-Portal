<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$jsonPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "featured_alumni" . DIRECTORY_SEPARATOR . "featured_alumni.json";

if (!file_exists($jsonPath)) {
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => [],
        "message" => "No featured alumni records configured."
    ]);
    exit;
}

$raw = file_get_contents($jsonPath);
$records = json_decode($raw, true);

if (!is_array($records)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Invalid featured alumni configuration."
    ]);
    exit;
}

usort($records, function ($a, $b) {
    $dateA = strtotime($a["visit_date"] ?? "1970-01-01");
    $dateB = strtotime($b["visit_date"] ?? "1970-01-01");
    return $dateB <=> $dateA;
});

$limit = isset($_GET["limit"]) ? max(1, min(12, (int)$_GET["limit"])) : 8;
$data = array_slice($records, 0, $limit);

echo json_encode([
    "success" => true,
    "count" => count($data),
    "data" => $data
]);

