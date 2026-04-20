<?php
// Entry point for all API requests.
// Handles CORS, routes to the correct controller method.

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/PatientController.php';
require_once __DIR__ . '/routes/api.php';
