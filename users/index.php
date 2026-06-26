<?php

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

/*
|--------------------------------------------------------------------------
| GET SINGLE USER
|--------------------------------------------------------------------------
*/

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

    if ($result->num_rows === 0) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);

        exit;
    }

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "user" => $result->fetch_assoc()
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET ALL USERS (ADMIN ONLY)
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

$result = $conn->query("
    SELECT
        id,
        name,
        email,
        role,
        group_id
    FROM users
    ORDER BY id ASC
");

$users = [];

while ($row = $result->fetch_assoc()) {

    $users[] = $row;

}

http_response_code(200);

echo json_encode([
    "success" => true,
    "users" => $users
]);

?>