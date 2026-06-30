<?php

require_once "../cors.php";

header("Content-Type: application/json");

require_once "../database.php";
require_once "../auth.php";

/*
|--------------------------------------------------------------------------
| GET SINGLE GROUP
|--------------------------------------------------------------------------
*/

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            description,
            created_at
        FROM groups
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Group not found."
        ]);

        exit;
    }

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "group" => $result->fetch_assoc()
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET ALL GROUPS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT
        id,
        name,
        description,
        created_at
    FROM groups
    ORDER BY id ASC
");

$groups = [];

while ($row = $result->fetch_assoc()) {
    $groups[] = $row;
}

http_response_code(200);

echo json_encode([
    "success" => true,
    "count" => count($groups),
    "groups" => $groups
]);

?>