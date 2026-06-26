<?php
session_start();
header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

// only logged-in users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// GET single user
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("
        SELECT id, name, email, role, group_id
        FROM users
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    echo json_encode($user ?: ["error" => "User not found"]);
    exit;
}

// ADMIN ONLY → GET ALL USERS
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$result = $conn->query("
    SELECT id, name, email, role, group_id
    FROM users
");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);