<?php
// Route table — maps URI + method to controller actions.
// Module 03: extend this file with doctors, referrals, and the nested route.

$uri    = strtok($_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];

$path = ltrim($uri, '/');

$patient  = new PatientController($pdo);
$doctor   = new DoctorController($pdo);
$referral = new ReferralController($pdo);

/*
 * CONCEPT: A router inspects the incoming URL and HTTP method, then calls the right
 * controller method. This file is a chain of if/elseif blocks — each block handles
 * one URL pattern. The PHP match() expression inside each block picks the right
 * controller method based on the HTTP method (GET, POST, PUT, DELETE).
 *
 * ROUTE ORDER MATTERS:
 * More specific patterns must come before generic ones.
 * /api/patients/{id}/referrals  ← match this FIRST (ends with /referrals)
 * /api/patients/{id}            ← match this SECOND (would otherwise consume the nested URL too)
 *
 * REGEX PATTERN: preg_match('#^api/patients/(\d+)$#', $path, $m)
 *   #      = delimiter (avoids escaping /)
 *   ^  $   = anchors — exact match from start to end of string
 *   (\d+)  = capture group: one or more digits → stored in $m[1]
 *
 * STEPS (for each new resource):
 * 1. Add an elseif ($path === 'api/resource') block for the collection URL
 * 2. Add an elseif (preg_match(...)) block for the single-item URL
 * 3. Inside each block, use match ($method) to call the right controller method
 * 4. Always include a default => respond405() arm in every match()
 */

// ── Patients (complete — reference implementation from Module 02) ─────────────

if ($path === 'api/patients') {
    match ($method) {
        'GET'    => $patient->index(),
        'POST'   => $patient->store(),
        default  => respond405(),
    };
} elseif (preg_match('#^api/patients/(\d+)/referrals$#', $path, $m)) {
    /*
     * TODO: handle GET /api/patients/{id}/referrals (nested route)
     *
     * HINT: $id = (int) $m[1];
     *       Call the method on $referral that fetches by patient id.
     *       Only GET is supported here — everything else → respond405()
     *
     * NOTE: This block MUST come before the generic api/patients/{id} block below.
     *       If the order were reversed, the generic pattern would match first and
     *       the nested route would never be reached.
     */
} elseif (preg_match('#^api/patients/(\d+)$#', $path, $m)) {
    $id = (int) $m[1];
    match ($method) {
        'GET'    => $patient->show($id),
        'PUT'    => $patient->update($id),
        'DELETE' => $patient->destroy($id),
        default  => respond405(),
    };

// ── Doctors ───────────────────────────────────────────────────────────────────

} elseif ($path === 'api/doctors') {
    /*
     * TODO: handle the doctors collection
     *
     * EXPECTED ROUTES:
     *   GET  /api/doctors → $doctor->index()
     *   POST /api/doctors → $doctor->store()
     *   Anything else    → respond405()
     *
     * HINT: use the same match ($method) { ... } pattern as the patients block above.
     */
} elseif (preg_match('#^api/doctors/(\d+)$#', $path, $m)) {
    /*
     * TODO: handle a single doctor by id
     *
     * EXPECTED ROUTES:
     *   GET    /api/doctors/{id} → $doctor->show($id)
     *   PUT    /api/doctors/{id} → $doctor->update($id)
     *   DELETE /api/doctors/{id} → $doctor->destroy($id)
     *
     * HINT: (int) $m[1] gives you the id as an integer.
     */

// ── Referrals ─────────────────────────────────────────────────────────────────

} elseif ($path === 'api/referrals') {
    /*
     * TODO: handle the referrals collection
     *
     * EXPECTED ROUTES:
     *   GET  /api/referrals → $referral->index()
     *   POST /api/referrals → $referral->store()
     */
} elseif (preg_match('#^api/referrals/(\d+)$#', $path, $m)) {
    /*
     * TODO: handle a single referral by id
     *
     * EXPECTED ROUTES:
     *   GET /api/referrals/{id} → $referral->show($id)
     *   PUT /api/referrals/{id} → $referral->update($id)
     *
     * NOTE: there is no DELETE for referrals in this module.
     */

} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Route not found']);
}

function respond405(): void {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Method not allowed']);
    exit;
}
