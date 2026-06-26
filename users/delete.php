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

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "User ID required"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["message" => "User deleted"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Delete failed"]);
}