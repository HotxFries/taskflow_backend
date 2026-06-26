<?php
session_start();
header("Content-Type: application/json");

// DB connection
require_once "db.php"; // make sure this exists

// ---------------------------
// AUTH CHECK
// ---------------------------
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// ---------------------------
// HELPER: ADMIN CHECK
// ---------------------------
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ---------------------------
// GET METHOD HANDLING
// ---------------------------
$method = $_SERVER['REQUEST_METHOD'];

// ===========================
// GET USERS
// ===========================
if ($method === 'GET') {

    // If user wants single user: users.php?id=1
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

        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "User not found"]);
        }

        exit;
    }

    // Admin-only: get all users
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(["error" => "Access denied"]);
        exit;
    }

    // Optional filter: users.php?group_id=1
    $query = "
        SELECT id, name, email, role, group_id 
        FROM users
    ";

    if (isset($_GET['group_id'])) {
        $group_id = intval($_GET['group_id']);
        $query .= " WHERE group_id = $group_id";
    }

    $result = $conn->query($query);

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
    exit;
}

// ===========================
// INVALID METHOD
// ===========================
http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
?>