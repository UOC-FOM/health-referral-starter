<?php
// Route table — maps incoming URI + HTTP method to a controller action.
// Module 03 will extend this file with doctors and referrals routes.

$uri    = strtok($_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];

// Strip the leading slash so matching patterns are cleaner
$path = ltrim($uri, '/');

$patient = new PatientController($pdo);

/*
 * CONCEPT: A router inspects the incoming URL and HTTP method, then
 * calls the correct controller method. Each URL pattern represents one
 * resource action. The two patterns you need are:
 *   - "api/patients"        (exact match) → collection: list all or create one
 *   - "api/patients/{id}"   (regex match) → single item: get, update, or delete
 *
 * STEPS:
 * 1. Check if $path equals 'api/patients' exactly.
 *    Inside, use a match() on $method to call $patient->index() (GET)
 *    or $patient->store() (POST).
 * 2. Check if $path matches the pattern 'api/patients/{id}' using preg_match.
 *    Cast the captured id to (int). Use a match() on $method to call
 *    $patient->show($id), $patient->update($id), or $patient->destroy($id).
 * 3. If neither pattern matches, return a 404 JSON response.
 *
 * HINT: preg_match('#^api/patients/(\d+)$#', $path, $m) captures the id in $m[1].
 *       PHP 8 match() syntax: match($method) { 'GET' => expr, default => expr }
 *       A respond405() helper function is useful for unsupported methods.
 *
 * EXPECTED ROUTES:
 *   GET    /api/patients      → $patient->index()
 *   POST   /api/patients      → $patient->store()
 *   GET    /api/patients/{id} → $patient->show($id)
 *   PUT    /api/patients/{id} → $patient->update($id)
 *   DELETE /api/patients/{id} → $patient->destroy($id)
 */
// TODO: implement route matching and dispatch
