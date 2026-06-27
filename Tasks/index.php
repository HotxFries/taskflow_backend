<?php

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

$user_id = $user['id'];
$role = $user['role'];

/*
|--------------------------------------------------------------------------
| ADMIN - VIEW ALL TASKS
|--------------------------------------------------------------------------
*/

if ($role === "admin") {

    $stmt = $conn->prepare("
        SELECT
            tasks.id,
            tasks.title,
            tasks.description,
            tasks.project_name,
            tasks.priority,
            tasks.type,
            tasks.status,
            tasks.assigned_to,
            tasks.created_by,
            tasks.group_id,
            tasks.due_date,
            tasks.due_time,
            tasks.created_at,
            tasks.updated_at,

            creator.name AS created_by_name,
            assignee.name AS assigned_to_name,
            groups.name AS group_name

        FROM tasks

        LEFT JOIN users AS creator
            ON tasks.created_by = creator.id

        LEFT JOIN users AS assignee
            ON tasks.assigned_to = assignee.id

        LEFT JOIN groups
            ON tasks.group_id = groups.id

        ORDER BY tasks.created_at DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $tasks = [];

    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }

    echo json_encode([
        "success" => true,
        "tasks" => $tasks
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| USER - VIEW OWN + GROUP TASKS
|--------------------------------------------------------------------------
*/

/*
Get user's group_id
*/

$stmt = $conn->prepare("
    SELECT group_id
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$userData = $stmt->get_result()->fetch_assoc();

$groupId = $userData['group_id'];

/*
|--------------------------------------------------------------------------
| SAFETY CHECK
|--------------------------------------------------------------------------
*/

if ($groupId === null) {

    echo json_encode([
        "success" => true,
        "tasks" => []
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET USER TASKS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.project_name,
        tasks.priority,
        tasks.type,
        tasks.status,
        tasks.assigned_to,
        tasks.created_by,
        tasks.group_id,
        tasks.due_date,
        tasks.due_time,
        tasks.created_at,
        tasks.updated_at,

        creator.name AS created_by_name,
        assignee.name AS assigned_to_name,
        groups.name AS group_name

    FROM tasks

    LEFT JOIN users AS creator
        ON tasks.created_by = creator.id

    LEFT JOIN users AS assignee
        ON tasks.assigned_to = assignee.id

    LEFT JOIN groups
        ON tasks.group_id = groups.id

    WHERE
        tasks.created_by = ?
        OR tasks.group_id = ?

    ORDER BY tasks.created_at DESC
");

$stmt->bind_param("ii", $user_id, $groupId);

$stmt->execute();

$result = $stmt->get_result();

$tasks = [];

while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode([
    "success" => true,
    "tasks" => $tasks
]);

?>