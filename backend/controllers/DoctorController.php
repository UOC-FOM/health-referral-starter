<?php

/**
 * DoctorController
 *
 * Handles all CRUD operations for the doctors resource.
 * Each doctor may have zero or more phone numbers (doctor_phones table).
 * Phone numbers are returned as an array in every response.
 *
 * Pattern: identical to PatientController — read that first, then apply it here.
 */
class DoctorController
{
    public function __construct(private PDO $db) {}

    /**
     * GET /api/doctors
     * Returns all doctors with their phone numbers aggregated into an array.
     */
    public function index(): void
    {
        /*
         * CONCEPT: Fetch all doctors joined with their phone numbers. Because one doctor
         * can have many phone numbers, a plain JOIN would produce duplicate doctor rows.
         * PostgreSQL's ARRAY_AGG() aggregates all phone numbers into a single array per
         * doctor, so you get one row per doctor with all phones included.
         *
         * STEPS:
         * 1. Write a SELECT with LEFT JOIN doctor_phones ON dp.doctor_id = d.id
         * 2. Use ARRAY_AGG(dp.phone_number ORDER BY dp.id) to collect phones
         * 3. Add FILTER (WHERE dp.phone_number IS NOT NULL) to exclude NULLs
         * 4. GROUP BY d.id (required by the aggregation)
         * 5. fetchAll() the results and map each row through formatDoctor()
         *
         * EXPECTED INPUT:  none
         * EXPECTED OUTPUT: { success: true, data: [{ id, name, specialization, phoneNumbers: [], createdAt }] }
         *
         * HINT: use COALESCE(ARRAY_AGG(...) FILTER (...), ARRAY[]::TEXT[]) to return an
         *       empty array (not NULL) when a doctor has no phone numbers.
         */
        // TODO: implement this method
    }

    /**
     * GET /api/doctors/{id}
     * Returns a single doctor with phone numbers. 404 if not found.
     */
    public function show(int $id): void
    {
        /*
         * CONCEPT: Same query as index() but filtered to one doctor. After fetching,
         * check if the row is falsy — PDO returns false when no row matches — and
         * return 404 before trying to format a missing row.
         *
         * STEPS:
         * 1. Same JOIN + ARRAY_AGG query as index(), add WHERE d.id = :id
         * 2. Use fetch() instead of fetchAll() (one row expected)
         * 3. If $row is falsy, return jsonResponse(false, null, 'Doctor not found', 404)
         * 4. Otherwise return jsonResponse(true, $this->formatDoctor($row))
         *
         * EXPECTED INPUT:  $id from URL segment (already cast to int by the router)
         * EXPECTED OUTPUT: { success: true, data: { id, name, specialization, phoneNumbers, createdAt } }
         *                  or { success: false, data: null, message: 'Doctor not found' } (404)
         *
         * HINT: fetch() returns false (not null) when no row is found.
         */
        // TODO: implement this method
    }

    /**
     * POST /api/doctors
     * Creates a new doctor. Requires: name, specialization.
     * Optional: phoneNumbers (array of strings).
     */
    public function store(): void
    {
        /*
         * CONCEPT: Creating a doctor may require two tables: doctors and doctor_phones.
         * If any INSERT fails halfway through, you must roll back both — otherwise you
         * have a doctor with no phones in an inconsistent state. Wrap both INSERTs in a
         * database transaction so they succeed or fail together (atomicity).
         *
         * STEPS:
         * 1. Read JSON body: json_decode(file_get_contents('php://input'), true) ?? []
         * 2. Validate: return 400 if name is empty, 400 if specialization is empty
         * 3. $this->db->beginTransaction()
         * 4. INSERT INTO doctors (name, specialization) VALUES (:name, :spec) RETURNING id, ...
         * 5. For each phone in $phoneNumbers, INSERT INTO doctor_phones (doctor_id, phone_number)
         * 6. $this->db->commit() then return jsonResponse(true, $this->formatDoctor(...), 'Doctor created', 201)
         * 7. catch (PDOException): $this->db->rollBack(), log, return 500
         *
         * EXPECTED INPUT:  { "name": "Dr. Silva", "specialization": "Psychiatry", "phoneNumbers": ["0771234567"] }
         * EXPECTED OUTPUT: { success: true, data: { id, name, specialization, phoneNumbers, createdAt }, message: "Doctor created" } (201)
         *
         * HINT: use $this->db->beginTransaction() / commit() / rollBack() — all three are PDO methods.
         */
        // TODO: implement this method
    }

    /**
     * PUT /api/doctors/{id}
     * Updates name and/or specialization. 404 if doctor does not exist.
     */
    public function update(int $id): void
    {
        /*
         * CONCEPT: Update the doctors row. After executing, check rowCount() — if zero
         * rows were affected, no doctor with that id exists and you should return 404.
         * Note: this endpoint updates only the doctor's name/specialization, not phone numbers.
         *
         * STEPS:
         * 1. Read body, validate name is not empty (return 400 if missing)
         * 2. Prepare UPDATE doctors SET name = :name, specialization = :spec WHERE id = :id
         * 3. Execute and check $stmt->rowCount() === 0 → return 404
         * 4. Otherwise return jsonResponse(true, null, 'Doctor updated')
         *
         * EXPECTED INPUT:  { "name": "Dr. Silva", "specialization": "Clinical Psychology" }
         * EXPECTED OUTPUT: { success: true, data: null, message: "Doctor updated" }
         *                  or 404 if id does not exist
         *
         * HINT: rowCount() returns the number of rows actually changed by the UPDATE.
         */
        // TODO: implement this method
    }

    /**
     * DELETE /api/doctors/{id}
     * Deletes a doctor. doctor_phones are removed automatically via ON DELETE CASCADE.
     * 404 if not found.
     */
    public function destroy(int $id): void
    {
        /*
         * CONCEPT: Delete the doctor row. The doctor_phones rows are removed automatically
         * because the foreign key is defined with ON DELETE CASCADE in the schema — you do
         * not need a separate DELETE on doctor_phones. Check rowCount() for 404.
         *
         * STEPS:
         * 1. Prepare DELETE FROM doctors WHERE id = :id
         * 2. Execute
         * 3. Check rowCount() === 0 → return 404
         * 4. Otherwise return jsonResponse(true, null, 'Doctor deleted')
         *
         * EXPECTED INPUT:  $id from URL
         * EXPECTED OUTPUT: { success: true, data: null, message: "Doctor deleted" }
         *                  or 404 if id does not exist
         *
         * HINT: ON DELETE CASCADE is set in the schema — trust it, do not write a second DELETE.
         */
        // TODO: implement this method
    }

    // -------------------------------------------------------------------------

    /**
     * Transforms a DB row (snake_case) to the camelCase API shape.
     * phone_numbers comes back from PostgreSQL as a string like "{07712345,07798765}" or "{}".
     */
    private function formatDoctor(array $row): array
    {
        /*
         * CONCEPT: PostgreSQL returns ARRAY_AGG results as a string like "{phone1,phone2}"
         * or "{}" when empty. You need to parse this string into a PHP array before
         * putting it in the JSON response.
         *
         * STEPS:
         * 1. Get $phones = $row['phone_numbers'] ?? '{}'
         * 2. trim($phones, '{}') removes the curly braces
         * 3. If trimmed string is empty, use []; otherwise explode(',', trimmed)
         * 4. Return an array with camelCase keys: id (int), name, specialization, phoneNumbers, createdAt
         *
         * HINT: (int) $row['id'] — always cast the id to int, PDO returns everything as strings.
         */
        // TODO: implement this method
        return [];
    }

    private function jsonResponse(bool $success, mixed $data, string $message = '', int $status = 200): void
    {
        http_response_code($status);
        echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
        exit;
    }
}
