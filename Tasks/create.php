<?php

require_once "../cors.php";

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

/*
|--------------------------------------------------------------------------
| Read JSON Input
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$project_name = trim($data['project_name'] ?? '');
$priority = trim($data['priority'] ?? 'Medium');
$type = trim($data['type'] ?? '');
$due_date = $data['due_date'] ?? null;
$due_time = $data['due_time'] ?? null;

/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

if ($title === '') {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Title is required."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get logged-in user info
|--------------------------------------------------------------------------
*/

$user_id = $user['id'];
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| Get user's group_id (from DB)
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT group_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

$user_group_id = $userData['group_id'];

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$assigned_to = null;
$status = "Pending";

/*
|--------------------------------------------------------------------------
| ROLE LOGIC
|--------------------------------------------------------------------------
*/

if ($role === "admin") {

    // Admin MUST assign task to someone
    $assigned_to = $data['assigned_to'] ?? null;

    if (!$assigned_to) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "assigned_to is required for admin."
        ]);
        exit;
    }

    // Get assigned user's group
    $stmt = $conn->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $assigned_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignedUser = $result->fetch_assoc();

    if (!$assignedUser) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Assigned user not found."
        ]);
        exit;
    }

    $group_id = $assignedUser['group_id'];
}

/*
|--------------------------------------------------------------------------
| USER LOGIC
|--------------------------------------------------------------------------
*/

if ($role === "user") {
    // Users cannot assign tasks
    $assigned_to = null;
    $group_id = $user_group_id;
}

/*
|--------------------------------------------------------------------------
| INSERT TASK
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO tasks
    (title, description, project_name, priority, type, due_date, due_time, assigned_to, created_by, group_id, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssiiis",
    $title,
    $description,
    $project_name,
    $priority,
    $type,
    $due_date,
    $due_time,
    $assigned_to,
    $user_id,
    $group_id,
    $status
);

if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create task."
    ]);
    exit;
}

$task_id = $conn->insert_id;

/*
|--------------------------------------------------------------------------
| SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/

http_response_code(201);

echo json_encode([
    "success" => true,
    "message" => "Task created successfully.",
    "task_id" => $task_id
]);