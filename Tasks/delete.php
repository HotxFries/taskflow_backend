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

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid task ID is required."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Check Task Exists
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, title
    FROM tasks
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Task not found."
    ]);

    exit;
}

$task = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Delete Task
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    DELETE FROM tasks
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete task."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Log Activity
|--------------------------------------------------------------------------
*/

$action = "Deleted Task";
$details = "Deleted task '{$task['title']}' (ID: {$id})";

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
    "message" => "Task deleted successfully."
]);

?>