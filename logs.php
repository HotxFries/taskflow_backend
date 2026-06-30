<?php

require_once "cors.php";

header("Content-Type: application/json");

require_once "database.php";
require_once "auth.php";

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
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

/*
|--------------------------------------------------------------------------
| GET LOGS
|--------------------------------------------------------------------------
*/

if (isset($_GET['user_id'])) {

    $userId = intval($_GET['user_id']);

    $stmt = $conn->prepare("
        SELECT
            logs.id,
            logs.user_id,
            users.name AS user_name,
            logs.action,
            logs.details,
            logs.created_at
        FROM logs
        INNER JOIN users
            ON logs.user_id = users.id
        WHERE logs.user_id = ?
        ORDER BY logs.created_at DESC
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query("
        SELECT
            logs.id,
            logs.user_id,
            users.name AS user_name,
            logs.action,
            logs.details,
            logs.created_at
        FROM logs
        INNER JOIN users
            ON logs.user_id = users.id
        ORDER BY logs.created_at DESC
    ");

}

/*
|--------------------------------------------------------------------------
| BUILD RESPONSE
|--------------------------------------------------------------------------
*/

$logs = [];

while ($row = $result->fetch_assoc()) {

    $logs[] = $row;

}

http_response_code(200);

echo json_encode([
    "success" => true,
    "count" => count($logs),
    "logs" => $logs
]);

?>