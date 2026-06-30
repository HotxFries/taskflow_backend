<?php

require_once "cors.php";

header("Content-Type: application/json");

require_once "database.php";
require_once "auth.php";

/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/

if ($user['role'] === "admin") {

    // Total Users
    $result = $conn->query("SELECT COUNT(*) AS total FROM users");
    $totalUsers = $result->fetch_assoc()['total'];

    // Total Groups
    $result = $conn->query("SELECT COUNT(*) AS total FROM groups");
    $totalGroups = $result->fetch_assoc()['total'];

    // Total Tasks
    $result = $conn->query("SELECT COUNT(*) AS total FROM tasks");
    $totalTasks = $result->fetch_assoc()['total'];

    // Pending Tasks
    $result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE status='Pending'");
    $pendingTasks = $result->fetch_assoc()['total'];

    // In Progress Tasks
    $result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE status='In Progress'");
    $inProgressTasks = $result->fetch_assoc()['total'];

    // Completed Tasks
    $result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE status='Completed'");
    $completedTasks = $result->fetch_assoc()['total'];

    // High Priority Tasks
    $result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE priority='High'");
    $highPriorityTasks = $result->fetch_assoc()['total'];

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "role" => "admin",
        "dashboard" => [

            "total_users" => (int)$totalUsers,
            "total_groups" => (int)$totalGroups,
            "total_tasks" => (int)$totalTasks,

            "pending_tasks" => (int)$pendingTasks,
            "in_progress_tasks" => (int)$inProgressTasks,
            "completed_tasks" => (int)$completedTasks,

            "high_priority_tasks" => (int)$highPriorityTasks
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| USER DASHBOARD
|--------------------------------------------------------------------------
*/

$userId = $user['id'];

// My Tasks
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$myTasks = $stmt->get_result()->fetch_assoc()['total'];

// Pending
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status='Pending'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc()['total'];

// In Progress
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status='In Progress'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$inProgress = $stmt->get_result()->fetch_assoc()['total'];

// Completed
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status='Completed'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$completed = $stmt->get_result()->fetch_assoc()['total'];

// High Priority
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND priority='High'
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$highPriority = $stmt->get_result()->fetch_assoc()['total'];

http_response_code(200);

echo json_encode([
    "success" => true,
    "role" => "user",
    "dashboard" => [

        "my_tasks" => (int)$myTasks,

        "pending_tasks" => (int)$pending,

        "in_progress_tasks" => (int)$inProgress,

        "completed_tasks" => (int)$completed,

        "high_priority_tasks" => (int)$highPriority
    ]
]);

?>