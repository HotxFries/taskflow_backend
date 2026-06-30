<?php

require_once "../cors.php";

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
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
| READ JSON
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($name === '') {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Group name is required."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE GROUP
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id FROM groups WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Group already exists."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CREATE GROUP
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO groups
    (name, description)
    VALUES (?, ?)
");

$stmt->bind_param(
    "ss",
    $name,
    $description
);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create group."
    ]);

    exit;
}

$groupId = $conn->insert_id;

/*
|--------------------------------------------------------------------------
| LOG ACTIVITY
|--------------------------------------------------------------------------
*/

$action = "Created Group";
$details = "Created group '{$name}' (ID: {$groupId})";

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
| SUCCESS
|--------------------------------------------------------------------------
*/

http_response_code(201);

echo json_encode([
    "success" => true,
    "message" => "Group created successfully.",
    "group_id" => $groupId
]);

?>