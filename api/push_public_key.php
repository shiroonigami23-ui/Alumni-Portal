<?php
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'public']);   // who may call this — enforced before anything below
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$publicKey = getenv('VAPID_PUBLIC_KEY') ?: '';
echo json_encode([
    "success" => true,
    "public_key" => $publicKey
]);

