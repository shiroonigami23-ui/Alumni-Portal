<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../models/Job.php';

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);

$stmt = $job->getAll();
$num = $stmt->rowCount();

if ($num > 0) {
    $job_arr = array();
    $job_arr["count"] = $num;
    $job_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        // Fetch description content from file
        $description_content = "Description not available.";
        if (isset($description_file_path)) {
            $description_content = $job->getDescription($description_file_path);
        }

        $job_item = array(
            "job_id" => $job_id,
            "company" => $company_name,
            "title" => $job_title,
            "description" => $description_content, // Use fetched content
            "location" => $location,
            "salary" => $salary_range,
            "apply_url" => $application_url,
            "type" => $job_type,
            "posted_by" => $poster_name,
            "posted_at" => $created_at
        );
        array_push($job_arr["records"], $job_item);
    }
    echo json_encode($job_arr);
} else {
    echo json_encode(array("count" => 0, "records" => []));
}
