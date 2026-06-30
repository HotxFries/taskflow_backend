<?php
/*
|--------------------------------------------------------------------------
| Shared CORS Handler
|--------------------------------------------------------------------------
| Include this at the very top of every endpoint, BEFORE database.php
| and auth.php, so that:
|   1. The browser's CORS preflight (OPTIONS) request gets a valid
|      response instead of being blocked.
|   2. Every real request (GET/POST/PUT/DELETE) carries the headers
|      needed for the JWT Authorization header to be allowed through.
*/

// Allow your React dev server. Add more origins here if needed
// (e.g. a deployed frontend URL) by checking $_SERVER['HTTP_ORIGIN'].
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Credentials: true");

// Preflight request: just acknowledge and stop here.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
