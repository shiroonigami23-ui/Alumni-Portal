<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

http_response_code(403);
echo json_encode([
    "success" => false,
    "message" => "Faculty self-registration is disabled. Faculty accounts are created manually by admin."
]);
?>
