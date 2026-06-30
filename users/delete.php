<?php

require_once "../cors.php";

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

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid user ID is required."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent Admin From Deleting Himself
|--------------------------------------------------------------------------
*/

if ($id == $user['id']) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "You cannot delete your own account."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Check User Exists
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, name
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);

    exit;
}

$deletedUser = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Delete User
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    DELETE FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete user."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Log Activity
|--------------------------------------------------------------------------
*/

$action = "Deleted User";
$details = "Deleted user '{$deletedUser['name']}' (ID: {$id})";

$log = $conn->prepare("
    INSERT INTO logs
    (user_id, action, details)
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
| Success
|--------------------------------------------------------------------------
*/

http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "User deleted successfully."
]);