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

$id = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($id <= 0 || $name === '') {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Group ID and name are required."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK GROUP EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM groups
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Group not found."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE NAME
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM groups
    WHERE name = ?
    AND id != ?
");

$stmt->bind_param("si", $name, $id);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Another group already has this name."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE GROUP
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE groups
    SET
        name = ?,
        description = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ssi",
    $name,
    $description,
    $id
);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update group."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| LOG ACTIVITY
|--------------------------------------------------------------------------
*/

$action = "Updated Group";
$details = "Updated group '{$name}' (ID: {$id})";

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

http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Group updated successfully."
]);

?>