<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$publicKey = getenv('VAPID_PUBLIC_KEY') ?: '';
echo json_encode([
    "success" => true,
    "public_key" => $publicKey
]);

