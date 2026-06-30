<?php

require_once "cors.php";

header("Content-Type: application/json");

require_once "database.php";
require_once "auth.php";

/*
|--------------------------------------------------------------------------
| Logged-in User
|--------------------------------------------------------------------------
*/

$userId = $user['id'];

/*
|--------------------------------------------------------------------------
| GET - Fetch Preferences
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === "GET") {

    $stmt = $conn->prepare("
        SELECT
            theme,
            notifications
        FROM preferences
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        http_response_code(200);

        echo json_encode([
            "success" => true,
            "preferences" => [
                "theme" => "light",
                "notifications" => true
            ]
        ]);

        exit;
    }

    $preferences = $result->fetch_assoc();

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "preferences" => [
            "theme" => $preferences['theme'],
            "notifications" => (bool)$preferences['notifications']
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| POST - Create or Update Preferences
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    $theme = strtolower(trim($data['theme'] ?? 'light'));
    $notifications = isset($data['notifications']) ? (int)$data['notifications'] : 1;

    if (!in_array($theme, ['light', 'dark'])) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid theme."
        ]);

        exit;
    }

    $notifications = $notifications ? 1 : 0;

    // Check if preferences already exist
    $stmt = $conn->prepare("
        SELECT id
        FROM preferences
        WHERE user_id = ?
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {

        // Update existing preferences
        $stmt = $conn->prepare("
            UPDATE preferences
            SET
                theme = ?,
                notifications = ?
            WHERE user_id = ?
        ");

        $stmt->bind_param(
            "sii",
            $theme,
            $notifications,
            $userId
        );

        $stmt->execute();

    } else {

        // Create preferences
        $stmt = $conn->prepare("
            INSERT INTO preferences
            (
                user_id,
                theme,
                notifications
            )
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param(
            "isi",
            $userId,
            $theme,
            $notifications
        );

        $stmt->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Log Activity
    |--------------------------------------------------------------------------
    */

    $action = "Updated Preferences";
    $details = "Updated preferences (Theme: {$theme}, Notifications: {$notifications})";

    $log = $conn->prepare("
        INSERT INTO logs
        (
            user_id,
            action,
            details
        )
        VALUES (?, ?, ?)
    ");

    $log->bind_param(
        "iss",
        $userId,
        $action,
        $details
    );

    $log->execute();

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" => "Preferences updated successfully."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Invalid Method
|--------------------------------------------------------------------------
*/

http_response_code(405);

echo json_encode([
    "success" => false,
    "message" => "Method not allowed."
]);

?>