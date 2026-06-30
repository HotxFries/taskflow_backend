<?php

require_once "../cors.php";

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

/*
|--------------------------------------------------------------------------
| Read Input
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

$task_id = $data['task_id'] ?? null;

if (!$task_id) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "task_id is required."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Task
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Task not found."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

$user_id = $user['id'];
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| Permission Check
|--------------------------------------------------------------------------
*/

if ($role !== "admin" && $task['created_by'] != $user_id) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "You cannot update this task."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Fields (safe defaults)
|--------------------------------------------------------------------------
*/

$title = $data['title'] ?? $task['title'];
$description = $data['description'] ?? $task['description'];
$project_name = $data['project_name'] ?? $task['project_name'];
$priority = $data['priority'] ?? $task['priority'];
$type = $data['type'] ?? $task['type'];
$due_date = $data['due_date'] ?? $task['due_date'];
$due_time = $data['due_time'] ?? $task['due_time'];

/*
|--------------------------------------------------------------------------
| STATUS RULES
|--------------------------------------------------------------------------
*/

$status = $task['status'];
$assigned_to = $task['assigned_to'];

if ($role === "admin") {

    // admin can do everything
    $status = $data['status'] ?? $task['status'];
    $assigned_to = $data['assigned_to'] ?? $task['assigned_to'];

} else {

    // user can ONLY update status + their own fields
    if (isset($data['status'])) {
        $status = $data['status'];
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE QUERY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE tasks
    SET title = ?,
        description = ?,
        project_name = ?,
        priority = ?,
        type = ?,
        due_date = ?,
        due_time = ?,
        status = ?,
        assigned_to = ?,
        updated_at = NOW()
    WHERE id = ?
");

$stmt->bind_param(
    "ssssssssii",
    $title,
    $description,
    $project_name,
    $priority,
    $type,
    $due_date,
    $due_time,
    $status,
    $assigned_to,
    $task_id
);

/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update task."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => "Task updated successfully."
]);