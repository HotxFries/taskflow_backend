<?php

header("Content-Type: application/json");

require_once "vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "TaskFlow@2026_SuperSecretKey_123456";

/*
|--------------------------------------------------------------------------
| Get Authorization Header
|--------------------------------------------------------------------------
*/

$headers = getallheaders();

$authHeader = '';

if (isset($headers['Authorization'])) {
    $authHeader = trim($headers['Authorization']);
} elseif (isset($headers['authorization'])) {
    $authHeader = trim($headers['authorization']);
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER['HTTP_AUTHORIZATION']);
} elseif (function_exists('apache_request_headers')) {
    $apacheHeaders = apache_request_headers();

    if (isset($apacheHeaders['Authorization'])) {
        $authHeader = trim($apacheHeaders['Authorization']);
    } elseif (isset($apacheHeaders['authorization'])) {
        $authHeader = trim($apacheHeaders['authorization']);
    }
}

/*
|--------------------------------------------------------------------------
| Check Token Exists
|--------------------------------------------------------------------------
*/

if (empty($authHeader)) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Authorization token missing."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Extract Token
|--------------------------------------------------------------------------
*/

if (stripos($authHeader, "Bearer ") === 0) {
    $token = substr($authHeader, 7);
} else {
    $token = $authHeader;
}

$token = trim($token);

/*
|--------------------------------------------------------------------------
| Verify Token
|--------------------------------------------------------------------------
*/

try {

    $decoded = JWT::decode(
        $token,
        new Key($secret_key, "HS256")
    );

    $user = [
        "id"    => $decoded->id,
        "name"  => $decoded->name,
        "email" => $decoded->email,
        "role"  => $decoded->role
    ];

} catch (Exception $e) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid or expired token.",
        // Uncomment the next line while debugging only.
        // "error" => $e->getMessage()
    ]);

    exit;
}