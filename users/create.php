<?php
session_start();
header("Content-Type: application/json");

require_once "../database.php";

// ADMIN ONLY
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

// read JSON input
$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? null;
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;
$group_id = $data['group_id'] ?? null;
$role = $data['role'] ?? 'user';

if (!$name || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Missing fields"]);
    exit;
}

// hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, role, group_id)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("ssssi", $name, $email, $hashedPassword, $role, $group_id);

if ($stmt->execute()) {
    echo json_encode(["message" => "User created"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create user"]);
}