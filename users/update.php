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

$id = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$role = trim($data['role'] ?? '');
$group_id = $data['group_id'] ?? null;

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($id <= 0 || $name === '' || $email === '' || $role === '') {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "ID, name, email and role are required."
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
| Check User Exists
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Duplicate Email Check
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ?
    AND id != ?
");

$stmt->bind_param("si", $email, $id);
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
| Update User
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE users
    SET
        name = ?,
        email = ?,
        role = ?,
        group_id = ?
    WHERE id = ?
");

$stmt->bind_param(
    "sssii",
    $name,
    $email,
    $role,
    $group_id,
    $id
);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update user."
    ]);

    exit;
}

http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "User updated successfully."
]);