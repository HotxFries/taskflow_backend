<?php

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

/*
|--------------------------------------------------------------------------
| Admin Only
|--------------------------------------------------------------------------
*/

if ($user['role'] !== "admin") {

    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Access denied."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Read JSON
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$role = trim($data['role'] ?? 'user');
$group_id = $data['group_id'] ?? null;

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($name === '' || $email === '' || $password === '') {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Name, email and password are required."
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);

    exit;
}

if (!in_array($role, ['admin', 'user'])) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid role."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Duplicate Email Check
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Email already exists."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Hash Password
|--------------------------------------------------------------------------
*/

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

/*
|--------------------------------------------------------------------------
| Insert User
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO users
    (name, email, password, role, group_id)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssi",
    $name,
    $email,
    $hashedPassword,
    $role,
    $group_id
);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create user."
    ]);

    exit;
}

http_response_code(201);

echo json_encode([
    "success" => true,
    "message" => "User created successfully.",
    "user_id" => $conn->insert_id
]);