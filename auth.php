<?php

header("Content-Type: application/json");

require_once "vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "TaskFlow@2026_SuperSecretKey_123456";

// Get all request headers
$headers = getallheaders();

// Check Authorization header
if (!isset($headers['Authorization'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Authorization token missing."
    ]);

    exit;
}

$authHeader = $headers['Authorization'];

// Remove "Bearer "
$token = str_replace("Bearer ", "", $authHeader);

try {

    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

    // Store user data for use in other APIs
    $user = [
        "id" => $decoded->id,
        "name" => $decoded->name,
        "email" => $decoded->email,
        "role" => $decoded->role
    ];

} catch (Exception $e) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid or expired token."
    ]);

    exit;
}