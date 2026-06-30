<?php

require_once "../cors.php";

header("Content-Type: application/json");

echo json_encode([
    "success" => true,
    "message" => "Logout successful. Please remove the token from the client."
]);