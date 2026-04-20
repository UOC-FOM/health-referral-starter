<?php

/**
 * PatientController
 *
 * Handles all CRUD operations for the patients resource.
 * Every public method corresponds to one route in routes/api.php.
 * All DB operations MUST use PDO prepared statements — never string concatenation.
 *
 * Response shape (use jsonResponse() for every output — never echo directly):
 *   { "success": true,  "data": {...}, "message": "" }
 *   { "success": false, "data": null,  "message": "Reason" }
 */
class PatientController
{
    public function __construct(private PDO $db) {}

    /**
     * GET /api/patients
     * Returns all patients joined with their district from the locations table.
     *
     * Expected output:
     *   { "success": true, "data": [ { "id": 1, "name": "...", "postalCode": "...",
     *     "district": "...", "createdAt": "..." }, ... ] }
     */
    public function index(): void
    {
        /*
         * CONCEPT: Fetching a list of records is the most common API operation.
         * You SELECT all rows from patients, LEFT JOIN locations so that patients
         * without a postal code still appear (district will be null). The result
         * is passed through formatPatient() to convert snake_case DB columns to
         * camelCase JSON keys before sending to the frontend.
         *
         * STEPS:
         * 1. Prepare a SELECT with LEFT JOIN on locations (postal_code = postal_code).
         * 2. Execute with no parameters (no WHERE clause needed).
         * 3. fetchAll() the results.
         * 4. Map each row through $this->formatPatient().
         * 5. Call $this->jsonResponse(true, $formattedRows).
         * 6. Wrap everything in try/catch(PDOException $e) — log the error,
         *    return jsonResponse(false, null, 'Failed to fetch patients', 500).
         *
         * HINT: array_map([$this, 'formatPatient'], $rows) applies formatPatient
         *       to every row at once.
         */
        // TODO: implement this method
    }

    /**
     * GET /api/patients/{id}
     * Returns a single patient by ID. Responds 404 if not found.
     *
     * @param int $id  Patient primary key from the URL segment.
     *
     * Expected output (found):
     *   { "success": true, "data": { "id": 2, "name": "...", "postalCode": "...",
     *     "district": "...", "createdAt": "..." } }
     * Expected output (not found):
     *   { "success": false, "data": null, "message": "Patient not found" }  HTTP 404
     */
    public function show(int $id): void
    {
        /*
         * CONCEPT: Fetching one record by its primary key is identical to index()
         * except you add WHERE p.id = :id and use fetch() instead of fetchAll().
         * If fetch() returns false the row does not exist — that is a 404, not a
         * 500. Never query the DB without a prepared statement even for a single id.
         *
         * STEPS:
         * 1. Prepare the same LEFT JOIN SELECT as index(), add WHERE p.id = :id.
         * 2. Execute with [':id' => $id].
         * 3. fetch() the result — if it is false, call jsonResponse with 404.
         * 4. Otherwise formatPatient() the row and jsonResponse 200.
         * 5. Wrap in try/catch(PDOException).
         *
         * HINT: $stmt->fetch() returns an associative array or false (not null).
         *       Check with if (!$row) to handle the not-found case.
         */
        // TODO: implement this method
    }

    /**
     * POST /api/patients
     * Creates a new patient. Requires: name (string). Optional: postalCode.
     *
     * Expected input body (JSON):
     *   { "name": "Amal Perera", "postalCode": "10100" }
     *
     * Expected output (success):
     *   { "success": true, "data": { "id": 3, "name": "Amal Perera",
     *     "postalCode": "10100", "district": null, "createdAt": "..." } }  HTTP 201
     * Expected output (missing name):
     *   { "success": false, "data": null, "message": "name is required" }  HTTP 400
     */
    public function store(): void
    {
        /*
         * CONCEPT: Creating a resource means reading a JSON body, validating
         * the required fields, then inserting a row. PostgreSQL's RETURNING clause
         * gives you back the generated id and timestamps in the same query — no
         * second SELECT needed. postalCode is optional (nullable FK) so it may
         * be absent from the body.
         *
         * STEPS:
         * 1. Read the request body:
         *      $body = json_decode(file_get_contents('php://input'), true) ?? [];
         * 2. Extract and trim name. If empty → jsonResponse false, 400.
         * 3. Extract postalCode — use null if not provided (?: null).
         * 4. Prepare INSERT INTO patients (name, postal_code) VALUES (:name, :postal_code)
         *    RETURNING id, name, postal_code, created_at
         * 5. Execute, fetch() the returned row, formatPatient() it.
         * 6. jsonResponse(true, $formatted, 'Patient created', 201).
         * 7. Wrap in try/catch(PDOException).
         *
         * HINT: trim($body['name'] ?? '') gives you an empty string if the key
         *       is missing. RETURNING works in PostgreSQL — it is not available in MySQL.
         */
        // TODO: implement this method
    }

    /**
     * PUT /api/patients/{id}
     * Updates name and postalCode for an existing patient.
     * Responds 404 if the patient does not exist.
     *
     * @param int $id  Patient primary key from the URL segment.
     *
     * Expected input body (JSON):
     *   { "name": "Amal K. Perera", "postalCode": "20000" }
     *
     * Expected output (success):
     *   { "success": true, "data": null, "message": "Patient updated" }
     * Expected output (not found):
     *   { "success": false, "data": null, "message": "Patient not found" }  HTTP 404
     */
    public function update(int $id): void
    {
        /*
         * CONCEPT: Updating a record follows the same read-validate-execute pattern
         * as store(), but uses UPDATE instead of INSERT. The critical difference is
         * that UPDATE may match zero rows (patient doesn't exist) — you must check
         * $stmt->rowCount() after execution and return 404 if it is 0.
         *
         * STEPS:
         * 1. Read and validate the request body (same as store).
         * 2. Prepare UPDATE patients SET name = :name, postal_code = :postal_code
         *    WHERE id = :id
         * 3. Execute with name, postal_code, and id bound.
         * 4. If $stmt->rowCount() === 0 → jsonResponse false, 404.
         * 5. Otherwise jsonResponse true, 200, 'Patient updated'.
         * 6. Wrap in try/catch(PDOException).
         *
         * HINT: rowCount() returns the number of rows actually changed by the query.
         *       Zero means no row had that id — treat it as not found.
         */
        // TODO: implement this method
    }

    /**
     * DELETE /api/patients/{id}
     * Deletes a patient by ID. Responds 404 if not found.
     *
     * @param int $id  Patient primary key from the URL segment.
     *
     * Expected output (success):
     *   { "success": true, "data": null, "message": "Patient deleted" }
     * Expected output (not found):
     *   { "success": false, "data": null, "message": "Patient not found" }  HTTP 404
     */
    public function destroy(int $id): void
    {
        /*
         * CONCEPT: Deleting is the simplest write operation — one prepared statement,
         * one rowCount() check, two possible responses. The same 404 pattern from
         * update() applies: if rowCount() is 0 the row never existed.
         *
         * STEPS:
         * 1. Prepare DELETE FROM patients WHERE id = :id
         * 2. Execute with [':id' => $id].
         * 3. If rowCount() === 0 → jsonResponse false, 404, 'Patient not found'.
         * 4. Otherwise jsonResponse true, 200, 'Patient deleted'.
         * 5. Wrap in try/catch(PDOException).
         *
         * HINT: No body to read for a DELETE request. The id comes entirely
         *       from the URL — it is already cast to int by the router.
         */
        // TODO: implement this method
    }

    // -------------------------------------------------------------------------

    /**
     * Transforms a DB row (snake_case keys) to the camelCase API response shape.
     * district may be null when postal_code has no matching row in locations.
     */
    private function formatPatient(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'name'       => $row['name'],
            'postalCode' => $row['postal_code'],
            'district'   => $row['district'] ?? null,
            'createdAt'  => $row['created_at'],
        ];
    }

    private function jsonResponse(bool $success, mixed $data, string $message = '', int $status = 200): void
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
        exit;
    }
}
