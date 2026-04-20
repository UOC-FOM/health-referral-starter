<?php
// PDO connection — reads from .env in project root.
// Included by index.php; exposes $pdo to the router.

$env = parse_ini_file(__DIR__ . '/../../.env');

$dsn = sprintf(
    "pgsql:host=%s;port=%s;dbname=%s;options='--search_path=%s'",
    $env['SUPABASE_DB_HOST'],
    $env['SUPABASE_DB_PORT'],
    $env['SUPABASE_DB_NAME'],
    $env['SUPABASE_DB_SCHEMA']
);

try {
    $pdo = new PDO($dsn, $env['SUPABASE_DB_USER'], $env['SUPABASE_DB_PASSWORD'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Database connection failed']);
    exit;
}
