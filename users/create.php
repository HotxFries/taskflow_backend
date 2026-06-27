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
        "message" => "Access denied. Only admin can create users."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Read JSON Input
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$password = trim($data['password'] ?? '');
$role = strtolower(trim($data['role'] ?? 'user'));
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
        "message" => "Invalid email format."
    ]);
    exit;
}

if (!in_array($role, ['user', 'admin'])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid role."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| ROLE RULES (IMPORTANT FIX)
|--------------------------------------------------------------------------
*/

// Only admin can create admin (you already ensured admin access, but this is safety)
if ($role === 'admin' && $user['role'] !== 'admin') {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Only admin can create another admin."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| GROUP RULES (IMPORTANT FIX)
|--------------------------------------------------------------------------
*/

if ($role === 'admin') {
    $group_id = null;
}

if ($role === 'user') {
    if (empty($group_id)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "group_id is required for users."
        ]);
        exit;
    }

    $group_id = (int)$group_id;
}

/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE EMAIL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Email already exists."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| HASH PASSWORD
|--------------------------------------------------------------------------
*/

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

/*
|--------------------------------------------------------------------------
| INSERT USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, role, group_id)
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

$userId = $conn->insert_id;

/*
|--------------------------------------------------------------------------
| LOG ACTIVITY
|--------------------------------------------------------------------------
*/

$action = "Created User";
$details = "Created {$role} '{$name}' (ID: {$userId})";

$log = $conn->prepare("
    INSERT INTO logs (user_id, action, details)
    VALUES (?, ?, ?)
");

$log->bind_param(
    "iss",
    $user['id'],
    $action,
    $details
);

$log->execute();

/*
|--------------------------------------------------------------------------
| SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/

http_response_code(201);

echo json_encode([
    "success" => true,
    "message" => "User created successfully.",
    "user_id" => $userId,
    "role" => $role
]);