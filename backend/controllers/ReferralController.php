<?php

/**
 * ReferralController
 *
 * Handles CRUD operations for the referrals resource.
 * Referrals link a patient to a doctor with a reason and status.
 * Also handles the nested route GET /api/patients/{id}/referrals.
 */
class ReferralController
{
    public function __construct(private PDO $db) {}

    /**
     * GET /api/referrals
     * Returns all referrals with patient name and doctor name joined.
     */
    public function index(): void
    {
        /*
         * CONCEPT: A referral row only stores patient_id and doctor_id — integers.
         * The frontend needs the actual names. JOIN both tables in a single query
         * instead of making separate requests for each name. Use AS aliases so both
         * name columns are distinguishable in the PHP result array.
         *
         * STEPS:
         * 1. SELECT r.*, p.name AS patient_name, d.name AS doctor_name
         * 2. FROM referrals r JOIN patients p ON p.id = r.patient_id
         * 3.                  JOIN doctors  d ON d.id = r.doctor_id
         * 4. ORDER BY r.id
         * 5. fetchAll() and map through formatReferral()
         *
         * EXPECTED INPUT:  none
         * EXPECTED OUTPUT: { success: true, data: [{ id, patientId, patientName, doctorId, doctorName, reason, status, referralDate, createdAt }] }
         *
         * HINT: Without aliases, both columns are called "name" and one overwrites the other.
         */
        // TODO: implement this method
    }

    /**
     * GET /api/referrals/{id}
     * Returns a single referral. 404 if not found.
     */
    public function show(int $id): void
    {
        /*
         * CONCEPT: Same JOIN query as index() but filtered to one referral with WHERE r.id = :id.
         * Fetch one row and check for 404 the same way as in PatientController::show().
         *
         * STEPS:
         * 1. Same JOIN query as index(), add WHERE r.id = :id
         * 2. fetch() — returns false if not found
         * 3. If falsy → return 404
         * 4. Otherwise return jsonResponse(true, $this->formatReferral($row))
         *
         * EXPECTED INPUT:  $id from URL segment
         * EXPECTED OUTPUT: { success: true, data: { id, patientId, patientName, ... } }
         *                  or 404 if not found
         *
         * HINT: fetch() returns false, not null, when no row is found.
         */
        // TODO: implement this method
    }

    /**
     * POST /api/referrals
     * Creates a new referral. Requires: patientId, doctorId, reason.
     * status defaults to 'pending'; referralDate defaults to today.
     */
    public function store(): void
    {
        /*
         * CONCEPT: Validate required fields before inserting. The status field has
         * a CHECK constraint in the database, but validate it in PHP first so you
         * can return 422 (Unprocessable) with a clear message rather than a generic 500.
         * referralDate and status have sensible defaults — do not require them.
         *
         * STEPS:
         * 1. Read body: patientId (int), doctorId (int), reason (string), status (default 'pending'), referralDate (optional)
         * 2. Validate: 400 if patientId is 0, 400 if doctorId is 0, 400 if reason is empty
         * 3. Validate status: 422 if not one of 'pending', 'approved', 'completed'
         * 4. INSERT INTO referrals (patient_id, doctor_id, reason, status, referral_date)
         *    VALUES (:patient_id, :doctor_id, :reason, :status, COALESCE(:referral_date::DATE, CURRENT_DATE))
         *    RETURNING id, patient_id, doctor_id, reason, status, referral_date, created_at
         * 5. Return 201 with the new referral
         *
         * EXPECTED INPUT:  { "patientId": 2, "doctorId": 3, "reason": "Anxiety assessment" }
         * EXPECTED OUTPUT: { success: true, data: { id, patientId, ..., status: "pending" }, message: "Referral created" } (201)
         *
         * HINT: use in_array($status, ['pending', 'approved', 'completed'], true) — the third
         *       argument true enables strict (type-safe) comparison.
         */
        // TODO: implement this method
    }

    /**
     * PUT /api/referrals/{id}
     * Updates the status of a referral. 404 if not found.
     */
    public function update(int $id): void
    {
        /*
         * CONCEPT: Only the status is updatable for a referral — the patient, doctor,
         * and reason are set at creation and do not change. Validate the status value
         * before writing it (422 for invalid values, 400 if missing).
         *
         * STEPS:
         * 1. Read body, get status string
         * 2. Validate: 400 if empty, 422 if not in the valid set
         * 3. UPDATE referrals SET status = :status WHERE id = :id
         * 4. Check rowCount() → 404 if zero
         * 5. Return jsonResponse(true, null, 'Referral updated')
         *
         * EXPECTED INPUT:  { "status": "approved" }
         * EXPECTED OUTPUT: { success: true, data: null, message: "Referral updated" }
         *                  or 404/422 on error
         *
         * HINT: Valid statuses are 'pending', 'approved', 'completed' — match the CHECK constraint in the schema.
         */
        // TODO: implement this method
    }

    /**
     * GET /api/patients/{id}/referrals
     * Returns all referrals for a specific patient.
     * 404 if the patient does not exist (not just "no referrals found").
     */
    public function byPatient(int $patientId): void
    {
        /*
         * CONCEPT: A nested route expresses a relationship: "give me the referrals that
         * belong to this patient." Before querying referrals, verify the patient exists —
         * return 404 if not. This distinguishes "patient not found" from "patient found
         * but has no referrals" (which returns 200 with an empty array).
         *
         * STEPS:
         * 1. SELECT id FROM patients WHERE id = :id → fetch()
         * 2. If not found → return jsonResponse(false, null, 'Patient not found', 404)
         * 3. SELECT referrals (same JOIN query as index()) WHERE r.patient_id = :patient_id
         * 4. fetchAll() and map through formatReferral() — may return an empty array (that is correct)
         * 5. Return jsonResponse(true, $rows)
         *
         * EXPECTED INPUT:  $patientId from URL (e.g. /api/patients/7/referrals)
         * EXPECTED OUTPUT: { success: true, data: [...referrals for this patient...] }
         *                  or { success: false, message: 'Patient not found' } (404)
         *
         * HINT: An empty array [] is a valid response — it means the patient exists but has
         *       no referrals yet. Do not return 404 for an empty list.
         */
        // TODO: implement this method
    }

    // -------------------------------------------------------------------------

    /**
     * Transforms a DB row (snake_case) to the camelCase API shape.
     */
    private function formatReferral(array $row): array
    {
        /*
         * CONCEPT: Convert snake_case DB column names to camelCase JSON keys, and cast
         * integer columns that PDO returns as strings. patient_name and doctor_name come
         * from the JOIN — they may be null if the referral was created without a JOIN
         * (e.g. right after INSERT RETURNING, before re-fetching with names).
         *
         * STEPS:
         * 1. Return an associative array mapping DB keys → camelCase names
         * 2. Cast id, patient_id, doctor_id to (int)
         * 3. Include: id, patientId, patientName, doctorId, doctorName, reason, status, referralDate, createdAt
         *
         * HINT: $row['patient_name'] ?? null — use ?? null for keys that may not always be present.
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
