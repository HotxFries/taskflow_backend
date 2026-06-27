<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "database.php";
require_once "vendor/autoload.php";

use Firebase\JWT\JWT;

$secret_key = "TaskFlow@2026_SuperSecretKey_123456";

$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['email']) ||
    !isset($data['password'])
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email and password are required."
    ]);

    exit;
}

$email = trim($data['email']);
$password = trim($data['password']);

$sql = "SELECT * FROM users WHERE email = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);

    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);

    exit;
}

$payload = [
    "id" => $user["id"],
    "name" => $user["name"],
    "email" => $user["email"],
    "role" => $user["role"],
    "iat" => time(),
    "exp" => time() + 86400
];

$jwt = JWT::encode($payload, $secret_key, "HS256");

http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Login successful.",
    "token" => $jwt,
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $user["role"]
    ]
]);