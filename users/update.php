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

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$name = $data['name'] ?? null;
$email = $data['email'] ?? null;
$group_id = $data['group_id'] ?? null;
$role = $data['role'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "User ID required"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE users
    SET name = ?, email = ?, group_id = ?, role = ?
    WHERE id = ?
");

$stmt->bind_param("ssisi", $name, $email, $group_id, $role, $id);

if ($stmt->execute()) {
    echo json_encode(["message" => "User updated"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Update failed"]);
}