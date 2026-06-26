<?php

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

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid group ID is required."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK GROUP EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT name
    FROM groups
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Group not found."
    ]);

    exit;
}

$group = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| CHECK USERS ASSIGNED
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM users
    WHERE group_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$userCount = $stmt->get_result()->fetch_assoc()['total'];

if ($userCount > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Cannot delete group. Users are still assigned to it."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK TASKS ASSIGNED
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE group_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$taskCount = $stmt->get_result()->fetch_assoc()['total'];

if ($taskCount > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Cannot delete group. Tasks are still assigned to it."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE GROUP
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    DELETE FROM groups
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete group."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| LOG ACTIVITY
|--------------------------------------------------------------------------
*/

$action = "Deleted Group";
$details = "Deleted group '{$group['name']}' (ID: {$id})";

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
    "message" => "Group deleted successfully."
]);

?>