<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

echo json_encode([
    "success" => true,
    "message" => "Logout successful. Please remove the token from the client."
]);