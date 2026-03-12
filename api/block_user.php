<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

http_response_code(410);
echo json_encode([
    "success" => false,
    "message" => "Blocking is disabled. Use report flow for harassment/spam."
]);
?>
