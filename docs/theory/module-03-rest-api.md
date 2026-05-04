# Module 03 — REST API Design

> **Relevant files in this project:**
> - `backend/routes/api.php` — route dispatcher (you extend this)
> - `backend/controllers/DoctorController.php` — CRUD for doctors (you implement this)
> - `backend/controllers/ReferralController.php` — CRUD for referrals (you implement this)
> - `backend/controllers/PatientController.php` — reference implementation (given complete)

---

## What This Module Is About

In Module 02 you built one resource — patients. The API worked, but a real application needs multiple resources with relationships between them. This module expands the API to cover doctors, referrals, and the relationship between patients and their referrals.

By the end of this module you will have a 14-endpoint API with:

```
Patients      → already complete (5 endpoints)
Doctors       → 5 new endpoints
Referrals     → 4 new endpoints + 1 nested route
```

The focus is not just on making the endpoints work — it is on making them work *correctly*. REST is about using HTTP the way it was designed to be used: the right method, the right status code, the right URL shape.

---

## 1. What is REST?

**REST** stands for Representational State Transfer. It is an architectural style described by Roy Fielding in his 2000 PhD dissertation. It is not a protocol or a standard — it is a set of constraints about how to design web APIs.

The most important constraint for this project is the **uniform interface**: your API should expose resources (things), not actions (verbs). The HTTP method tells you what to do; the URL tells you what to do it to.

```
BAD  (action-oriented):  POST /api/createDoctor
BAD  (action-oriented):  GET  /api/deleteReferral?id=5
GOOD (resource-oriented): POST /api/doctors
GOOD (resource-oriented): DELETE /api/referrals/5
```

> "REST ignores the details of component implementation and protocol syntax in order to focus on the roles of components, the constraints upon their interaction with other components, and their interpretation of significant data elements."
>
> — Roy Fielding, [Architectural Styles and the Design of Network-based Software Architectures](https://www.ics.uci.edu/~fielding/pubs/dissertation/top.htm) (2000)

A REST API that uses HTTP correctly is sometimes called **RESTful**. Your API does not need to satisfy all six of Fielding's constraints to be useful — in practice, "RESTful" usually means: plural noun URLs, correct HTTP methods, meaningful status codes, and consistent JSON responses.

---

## 2. HTTP Methods — Semantics, Not Just Syntax

HTTP methods have defined meanings. Using them correctly means clients can reason about what your API does before even reading the documentation.

| Method | Meaning | Safe? | Idempotent? |
|--------|---------|-------|-------------|
| GET | Read a resource — never modify data | Yes | Yes |
| POST | Create a new resource | No | No |
| PUT | Replace/update an existing resource | No | Yes |
| DELETE | Remove a resource | No | Yes |

**Safe** means the request has no side effects — calling it 100 times changes nothing. GET must always be safe. Never use GET for operations that delete or modify data.

**Idempotent** means calling it multiple times produces the same result as calling it once. DELETE /api/doctors/5 deletes doctor 5; calling it again when the doctor is already gone should return 404 — the state of the system is the same (doctor 5 is gone either way).

In this project:
- `GET /api/doctors` — read all doctors (safe)
- `POST /api/doctors` — create a new doctor (not safe, not idempotent — creates a new row each time)
- `PUT /api/doctors/3` — update doctor 3's name (idempotent — calling twice with the same body leaves the same result)
- `DELETE /api/doctors/3` — delete doctor 3 (idempotent — doctor 3 is gone regardless of how many times you call it)

---

## 3. URL Design — Resources and Relationships

URLs should identify **things**, not **actions**. The thing is the resource; the action is the HTTP method.

### Basic resource URLs

```
GET    /api/doctors        → collection of all doctors
POST   /api/doctors        → create a new doctor (body contains data)
GET    /api/doctors/3      → single doctor with id=3
PUT    /api/doctors/3      → update doctor with id=3
DELETE /api/doctors/3      → delete doctor with id=3
```

This is the same pattern you implemented for patients in Module 02.

### Nested resource URLs

Some resources only make sense in the context of another resource. A referral belongs to a patient. When you want all referrals for a specific patient, you can express that relationship in the URL:

```
GET /api/patients/7/referrals   → all referrals where patient_id = 7
```

This is a **nested route**. The URL says: "give me the referrals that belong to patient 7." It is clearer than `GET /api/referrals?patientId=7` because:
1. It encodes the relationship structurally
2. It makes it natural to check the parent exists (patient 7 must exist, or it is a 404)
3. It matches how React will think about data: "I have a patient, I need their referrals"

> "URI path segments can be designed to express the hierarchy or structure of data, making the API more intuitive."
>
> — [RESTful Web Services](https://www.oreilly.com/library/view/restful-web-services/9780596529260/), Richardson & Ruby

---

## 4. Status Codes — The Language of HTTP

HTTP status codes tell the client what happened, without them having to parse the response body. Your API uses these codes consistently across every endpoint.

### 2xx — Success

| Code | Name | When to use |
|------|------|-------------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST that created a new resource |

Always return **201** from a successful `store()` method, not 200. The difference matters: it tells the client "something new was created."

### 4xx — Client Error

| Code | Name | When to use |
|------|------|-------------|
| 400 | Bad Request | Required field is missing or malformed |
| 404 | Not Found | The resource with that id does not exist |
| 405 | Method Not Allowed | HTTP method not supported on this route |
| 422 | Unprocessable Entity | Input is syntactically valid but semantically wrong |

**400 vs 422:** Missing the `name` field entirely → 400. Sending `"status": "cancelled"` when only `pending`, `approved`, and `completed` are valid → 422 (the request was understood, but the value is not acceptable).

### 5xx — Server Error

| Code | Name | When to use |
|------|------|-------------|
| 500 | Internal Server Error | Database exception, unexpected PHP error |

Never expose the raw exception message in a 500 response. Log it with `error_log()` and return a generic "Failed to ..." message to the client.

> "The reason phrases in the status codes are intended to give a short textual description of the status code to aid humans, not to give extended information."
>
> — [RFC 9110 — HTTP Semantics](https://httpwg.org/specs/rfc9110.html), §15

---

## 5. Multi-Resource Routing

In Module 02, `api.php` handled only patients. Adding two more resources means extending the router with more branches.

The router pattern is a chain of `if / elseif` blocks, each matching a URL pattern:

```php
if ($path === 'api/doctors') {
    match ($method) {
        'GET'   => $doctor->index(),
        'POST'  => $doctor->store(),
        default => respond405(),
    };
} elseif (preg_match('#^api/doctors/(\d+)$#', $path, $m)) {
    $id = (int) $m[1];
    match ($method) {
        'GET'    => $doctor->show($id),
        'PUT'    => $doctor->update($id),
        'DELETE' => $doctor->destroy($id),
        default  => respond405(),
    };
}
```

### Why `match` instead of `if`/`switch`?

PHP 8's `match` expression is exhaustive and strict. Unlike `switch`, it uses strict comparison (`===`) and requires all arms to return a value or throw. The `default` arm handles anything not listed, which is where `respond405()` belongs.

### Regex routing

For URLs with a dynamic segment like `/api/doctors/42`, the router uses `preg_match`:

```php
preg_match('#^api/doctors/(\d+)$#', $path, $m)
```

- `#` — delimiter (avoids escaping forward slashes)
- `^` / `$` — anchors (exact match from start to end)
- `(\d+)` — capture group: one or more digits
- `$m[1]` — contains the captured id string; cast to int immediately with `(int)`

Always cast URL segments to the correct type before passing them to the controller. Never pass raw strings as ids.

---

## 6. The Nested Route — Order Matters

The nested route `GET /api/patients/{id}/referrals` must be matched **before** the generic `GET /api/patients/{id}` route. If it comes after, the regex `api/patients/(\d+)` will match first and the nested route will never be reached.

```php
// CORRECT — specific pattern first
} elseif (preg_match('#^api/patients/(\d+)/referrals$#', $path, $m)) {
    $referral->byPatient((int) $m[1]);
} elseif (preg_match('#^api/patients/(\d+)$#', $path, $m)) {
    // ...patients/{id} handling
}

// WRONG — generic pattern first swallows nested routes
} elseif (preg_match('#^api/patients/(\d+)$#', $path, $m)) {
    // This block runs for /api/patients/7/referrals because
    // the regex matches "api/patients/7" and ignores "/referrals"
    // ... but wait — the $ anchor prevents this
```

Actually, `#^api/patients/(\d+)$#` will NOT match `api/patients/7/referrals` because the `$` anchor requires the string to end after the digits. Still, always put more specific patterns first — it is correct by intent and prevents subtle bugs as the router grows.

---

## 7. The Response Envelope

Every endpoint in this project returns the same JSON structure:

```json
{
  "success": true,
  "data": { ... },
  "message": ""
}
```

or

```json
{
  "success": false,
  "data": null,
  "message": "Doctor not found"
}
```

This is the **envelope pattern**. The outer wrapper is always predictable; the actual resource lives inside `data`. This makes it easy for the frontend to write one error-handling function that works for every API call:

```js
const res = await api.get('/doctors');
if (!res.data.success) {
  showError(res.data.message);
  return;
}
setDoctors(res.data.data);
```

The `jsonResponse()` private method enforces this:

```php
private function jsonResponse(bool $success, mixed $data, string $message = '', int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}
```

Copy this method verbatim into every controller. Never echo JSON anywhere else.

---

## 8. Transactions — When Multiple Inserts Must Succeed Together

Creating a doctor with phone numbers requires two INSERT statements: one into `doctors`, one (or more) into `doctor_phones`. If the first INSERT succeeds but the second fails, you have a doctor with no phone number and a half-committed record.

**Transactions** solve this. Either both INSERTs commit, or neither does:

```php
$this->db->beginTransaction();
try {
    // INSERT into doctors
    // INSERT into doctor_phones (for each number)
    $this->db->commit();
} catch (PDOException $e) {
    $this->db->rollBack();   // undo everything if any INSERT fails
    error_log($e->getMessage());
    $this->jsonResponse(false, null, 'Failed to create doctor', 500);
}
```

This is called **atomicity** — the operation either happens completely or not at all. It is the "A" in ACID (Atomicity, Consistency, Isolation, Durability), the four properties that define reliable database transactions.

> "A transaction is a unit of work that is performed against a database. Transactions are units or sequences of work accomplished in a logical order, whether in a manual fashion by a user or automatically by some sort of a database program."
>
> — [PostgreSQL Documentation — Transactions](https://www.postgresql.org/docs/current/tutorial-transactions.html)

---

## 9. Validating Enum Fields

The `referrals` table has a `status` column with a database-level CHECK constraint:

```sql
CHECK (status IN ('pending', 'approved', 'completed'))
```

You should validate this in PHP **before** sending it to the database:

```php
if (!in_array($status, ['pending', 'approved', 'completed'], true)) {
    $this->jsonResponse(false, null, 'status must be pending, approved, or completed', 422);
    return;
}
```

Why validate in PHP if the database also checks? Two reasons:
1. A database constraint violation throws a `PDOException`, which you catch as a generic 500. Catching it early lets you return 422 with a helpful message instead.
2. Defense in depth — the database is your last line of defense, not your first.

The third argument `true` to `in_array` enables strict mode (type-safe comparison). Without it, PHP will coerce types, which can lead to unexpected matches.

---

## 10. Joining Related Data

A referral row on its own only has `patient_id` and `doctor_id` — integers. The frontend needs the names. Instead of making three round-trips (one for the referral, one for the patient, one for the doctor), join them in a single query:

```sql
SELECT r.id, r.patient_id, p.name AS patient_name,
       r.doctor_id, d.name AS doctor_name,
       r.reason, r.status, r.referral_date, r.created_at
FROM referrals r
JOIN patients p ON p.id = r.patient_id
JOIN doctors  d ON d.id = r.doctor_id
WHERE r.id = :id
```

The `AS patient_name` and `AS doctor_name` aliases give the columns a predictable name in the PHP result array. Without aliases, both columns would just be named `name` and one would overwrite the other.

In the `formatReferral()` method, these become `patientName` and `doctorName` in the JSON response — following the camelCase convention established in Module 02.

---

## 11. Project Connection

| Concept | Where it appears |
|---------|-----------------|
| REST resource design | `backend/routes/api.php` — URL structure |
| HTTP method semantics | `backend/routes/api.php` — `match ($method)` arms |
| Status codes | Every `jsonResponse()` call in the controllers |
| Nested routes | `api.php` — `#^api/patients/(\d+)/referrals$#` pattern |
| Route ordering | `api.php` — nested pattern before generic `patients/{id}` |
| Response envelope | `jsonResponse()` private method in each controller |
| Transactions | `DoctorController::store()` — beginTransaction / commit / rollBack |
| Enum validation | `ReferralController::store()` and `update()` |
| JOIN queries | `ReferralController::index()`, `show()`, `byPatient()` |
| Phone number aggregation | `DoctorController::index()`, `show()` — `ARRAY_AGG` + `FILTER` |

---

## Further Reading

- [MDN — HTTP Methods](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)
- [MDN — HTTP Status Codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)
- [RFC 9110 — HTTP Semantics](https://httpwg.org/specs/rfc9110.html) — the formal spec for HTTP methods and status codes
- [Roy Fielding's Dissertation — Chapter 5: REST](https://www.ics.uci.edu/~fielding/pubs/dissertation/rest_arch_style.htm)
- [PostgreSQL — Transactions](https://www.postgresql.org/docs/current/tutorial-transactions.html)
- [PHP — PDO Transactions](https://www.php.net/manual/en/pdo.begintransaction.php)
- [PHP — `in_array` with strict mode](https://www.php.net/manual/en/function.in-array.php)
