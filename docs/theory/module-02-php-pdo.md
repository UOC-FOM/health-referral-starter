# Module 02 — PHP + PDO CRUD

> **Relevant files in this project:**
> - `backend/config/db.php` — PDO connection (given to you complete)
> - `backend/index.php` — entry point and CORS (given to you complete)
> - `backend/routes/api.php` — route dispatcher (you implement this)
> - `backend/controllers/PatientController.php` — CRUD methods (you implement these)

---

## What This Module Is About

In Module 01 you designed the database. In this module you build the **API layer** — the PHP code that sits between the database and the React frontend.

When React needs to display a list of patients, it does not talk to the database directly. It sends an HTTP request to your PHP server. PHP runs the SQL, gets the data back, and sends it to React as JSON.

```
React (browser)         PHP server             Supabase (PostgreSQL)
localhost:5173    →     localhost:8000    →     cloud database
               HTTP req              PDO query
               ←                     ←
             JSON res              SQL result
```

By the end of this module you will have a working API that can create, read, update, and delete patients — tested with real HTTP requests.

---

## 1. What is PHP Doing Here?

PHP is a server-side language — it runs on the server, not in the browser. In this project PHP has three responsibilities:

1. **Routing** — deciding which function handles each incoming request
2. **Data access** — running SQL queries against the database safely
3. **Responding** — sending back structured JSON the frontend can use

Every request comes in through `backend/index.php`. That file sets CORS headers, connects to the database, and hands off to the router.

> "PHP is a widely-used open source general-purpose scripting language that is especially suited for web development and can be embedded into HTML."
>
> — [PHP Manual — Introduction](https://www.php.net/manual/en/intro-whatis.php)

---

## 2. PDO — The Database Abstraction Layer

**PDO** (PHP Data Objects) is a built-in PHP extension that lets you talk to a database using a consistent interface — regardless of whether the database is PostgreSQL, MySQL, or SQLite.

In this project the database is **PostgreSQL** (hosted on Supabase), so we use the `pgsql` driver.

### Opening a Connection

Look at `backend/config/db.php`:

```php
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--search_path=$schema'";

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

Breaking this down:

| Part | What it does |
|------|-------------|
| `pgsql:host=...` | The DSN (Data Source Name) — tells PDO which database driver and server to use |
| `options='--search_path=$schema'` | Tells PostgreSQL to look in your student schema first |
| `ERRMODE_EXCEPTION` | Throws a `PDOException` when something goes wrong — so you can catch it |
| `FETCH_ASSOC` | Returns rows as `['name' => 'Alice']` instead of `[0 => 'Alice', 'name' => 'Alice']` |

The connection is stored in `$pdo` and passed to every controller.

### Why PDO and not `pg_query()`?

PHP has older functions like `pg_query()` that are specific to PostgreSQL. PDO is preferred because:
- It works with any database (your skills transfer)
- It has built-in support for prepared statements (critical for security)
- It has a consistent error handling model

> See: [PHP Manual — PDO](https://www.php.net/manual/en/book.pdo.php)

---

## 3. SQL Injection — Why Prepared Statements Are Non-Negotiable

**SQL injection** is the most common and dangerous web vulnerability. It happens when user input is concatenated directly into a SQL string.

### The Dangerous Way (NEVER do this)

```php
$id = $_GET['id'];  // attacker sends: 1 OR 1=1; DROP TABLE patients; --
$result = $pdo->query("SELECT * FROM patients WHERE id = $id");
```

The query the database sees:
```sql
SELECT * FROM patients WHERE id = 1 OR 1=1; DROP TABLE patients; --
```

The `OR 1=1` returns every row. The `DROP TABLE` destroys your data. The `--` comments out anything after.

### The Safe Way — Prepared Statements (always do this)

```php
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute([':id' => $id]);
$patient = $stmt->fetch();
```

The database receives the query structure and the data **separately**. No matter what `$id` contains, it is treated as a plain value — never as SQL syntax.

> "Prepared statements are a feature of PDO used to execute the same SQL statement repeatedly with high efficiency and security. The data values are sent to the database server separately from the query, so an attacker cannot inject SQL commands through the data."
>
> — [PHP Manual — Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)

### Named Placeholders

This project uses **named placeholders** (`:id`, `:name`, `:postal_code`). They are more readable than positional `?` placeholders and order-independent:

```php
// Named — clear and safe
$stmt->execute([':name' => $name, ':postal_code' => $postal]);

// Positional — valid but order must match exactly
$stmt->execute([$name, $postal]);
```

---

## 4. The CRUD Pattern

**CRUD** stands for Create, Read, Update, Delete — the four operations every resource needs. In HTTP terms:

| CRUD | HTTP Method | URL | What it does |
|------|------------|-----|-------------|
| Read all | `GET` | `/api/patients` | Return every patient |
| Read one | `GET` | `/api/patients/{id}` | Return one patient |
| Create | `POST` | `/api/patients` | Insert a new patient |
| Update | `PUT` | `/api/patients/{id}` | Change an existing patient |
| Delete | `DELETE` | `/api/patients/{id}` | Remove a patient |

Each operation maps to one method in `PatientController`:

```
index()   → GET /api/patients
show()    → GET /api/patients/{id}
store()   → POST /api/patients
update()  → PUT /api/patients/{id}
destroy() → DELETE /api/patients/{id}
```

### Reading All Records (index)

```php
$stmt = $this->db->prepare(
    'SELECT p.id, p.name, p.postal_code, l.district, p.created_at
     FROM patients p
     LEFT JOIN locations l ON p.postal_code = l.postal_code
     ORDER BY p.id'
);
$stmt->execute();
$rows = $stmt->fetchAll();  // returns an array of associative arrays
```

`fetchAll()` returns every row. `LEFT JOIN` means patients without a postal code still appear — their `district` will just be `null`.

### Reading One Record (show)

```php
$stmt = $this->db->prepare(
    'SELECT ... FROM patients p LEFT JOIN locations l ... WHERE p.id = :id'
);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();  // returns one row, or false if not found

if (!$row) {
    // send a 404 — the patient does not exist
}
```

`fetch()` (not `fetchAll()`) returns one row or `false`. Always check for `false` before using the result.

### Creating a Record (store)

```php
// Read and validate the request body first
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($body['name'] ?? '');

if ($name === '') {
    // send 400 — name is required
}

$stmt = $this->db->prepare(
    'INSERT INTO patients (name, postal_code)
     VALUES (:name, :postal_code)
     RETURNING id, name, postal_code, created_at'
);
$stmt->execute([':name' => $name, ':postal_code' => $postalCode]);
$new = $stmt->fetch();  // RETURNING gives back the inserted row
```

`RETURNING` is a PostgreSQL feature — it gives you back the new row (including the auto-generated `id`) in the same query, without needing a second `SELECT`.

### Why `file_get_contents('php://input')`?

HTML forms send data as `application/x-www-form-urlencoded`, which PHP puts in `$_POST`. But REST API clients send `application/json` in the request body — PHP does not parse this automatically. `php://input` is a special stream that gives you the raw request body.

```php
$body = json_decode(file_get_contents('php://input'), true);
// $body is now a PHP associative array from the JSON
```

### Updating a Record (update)

```php
$stmt = $this->db->prepare(
    'UPDATE patients SET name = :name, postal_code = :postal_code WHERE id = :id'
);
$stmt->execute([':name' => $name, ':postal_code' => $postalCode, ':id' => $id]);

if ($stmt->rowCount() === 0) {
    // send 404 — no row matched, patient doesn't exist
}
```

`rowCount()` returns the number of rows the query actually changed. If it returns `0`, no patient had that id — treat it as not found.

### Deleting a Record (destroy)

```php
$stmt = $this->db->prepare('DELETE FROM patients WHERE id = :id');
$stmt->execute([':id' => $id]);

if ($stmt->rowCount() === 0) {
    // send 404
}
```

The same `rowCount()` pattern applies to DELETE.

> See: [GeeksforGeeks — CRUD in REST API using PHP](https://www.geeksforgeeks.org/php/crud-operation-in-rest-api-using-php/)

---

## 5. HTTP Status Codes

The status code in an HTTP response tells the client what happened. Always use the right one — don't return 200 for errors.

| Code | Meaning | When to use |
|------|---------|-------------|
| **200 OK** | Success | GET, PUT, DELETE succeeded |
| **201 Created** | Resource created | POST succeeded, new row inserted |
| **400 Bad Request** | Client sent invalid data | Required field missing |
| **404 Not Found** | Resource doesn't exist | `fetch()` returned false, or `rowCount()` was 0 |
| **500 Internal Server Error** | Server-side error | PDOException — log it, don't expose details |

```php
http_response_code(201);  // set before echo
echo json_encode([...]);
```

---

## 6. The JSON Response Envelope

Every response from this API follows the same shape — whether it succeeds or fails:

```json
{ "success": true,  "data": { ... }, "message": "" }
{ "success": false, "data": null,    "message": "Descriptive reason" }
```

This is called a **response envelope**. The frontend always knows:
- Check `success` first
- If true, use `data`
- If false, show `message` to the user

The `jsonResponse()` private method in each controller enforces this:

```php
private function jsonResponse(bool $success, mixed $data, string $message = '', int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;  // stop execution — nothing should be sent after the response
}
```

`exit` after the response ensures no accidental output (PHP warnings, extra whitespace) corrupts the JSON.

---

## 7. camelCase JSON Keys

The database uses `snake_case` column names (`postal_code`, `created_at`). The JavaScript/React frontend expects `camelCase` (`postalCode`, `createdAt`). The transformation happens in the controller before sending:

```php
private function formatPatient(array $row): array
{
    return [
        'id'         => (int) $row['id'],
        'name'       => $row['name'],
        'postalCode' => $row['postal_code'],   // snake → camel
        'district'   => $row['district'] ?? null,
        'createdAt'  => $row['created_at'],    // snake → camel
    ];
}
```

This keeps the database conventions (snake_case) and the frontend conventions (camelCase) clean on both sides.

---

## 8. Error Handling

Every database operation is wrapped in a `try/catch`:

```php
try {
    $stmt = $this->db->prepare("...");
    $stmt->execute([...]);
    $this->jsonResponse(true, $stmt->fetchAll());
} catch (PDOException $e) {
    error_log($e->getMessage());  // write to PHP's error log (your terminal)
    $this->jsonResponse(false, null, 'Failed to fetch patients', 500);
}
```

`error_log()` writes to PHP's error output — visible in your terminal when running `php -S`. The client only ever sees `"Failed to fetch patients"` — never the actual SQL error, database name, or credentials.

> **Rule:** Never expose internal error details to the API response. Log them server-side, return a safe message client-side.

---

## 9. The Router

The router in `backend/routes/api.php` reads the request URI and method, then calls the right controller method.

```
GET /api/patients        → $patient->index()
GET /api/patients/2      → $patient->show(2)
POST /api/patients       → $patient->store()
PUT /api/patients/2      → $patient->update(2)
DELETE /api/patients/2   → $patient->destroy(2)
```

The two URL patterns work differently:
- **Exact match** — `api/patients` (no id segment)
- **Regex match** — `api/patients/(\d+)` — the `(\d+)` captures the numeric id

```php
// Exact match
if ($path === 'api/patients') { ... }

// Regex match — captures the id
if (preg_match('#^api/patients/(\d+)$#', $path, $m)) {
    $id = (int) $m[1];  // $m[1] is the captured number
}
```

---

## 10. PHP 8.2 Features Used in This Module

This project uses modern PHP 8.2 syntax throughout:

**Constructor property promotion** — shorthand for declaring and assigning in one step:
```php
// Instead of:
private PDO $db;
public function __construct(PDO $db) { $this->db = $db; }

// PHP 8.2 shorthand:
public function __construct(private PDO $db) {}
```

**`match` expression** — cleaner than `switch`, returns a value, strict comparison:
```php
match ($method) {
    'GET'   => $patient->index(),
    'POST'  => $patient->store(),
    default => respond405(),
};
```

**Null coalescing** — safely read from arrays that may not have the key:
```php
$name = trim($body['name'] ?? '');  // empty string if 'name' key is missing
```

> See: [PHP 8.2 New Features](https://www.php.net/releases/8.2/en.php)

---

## 11. Project Connection

| Concept | Where it appears in the project |
|---------|--------------------------------|
| PDO connection | `backend/config/db.php` |
| DSN with schema | `pgsql:...options='--search_path=$schema'` in `db.php` |
| CORS + entry point | `backend/index.php` |
| Route dispatcher | `backend/routes/api.php` |
| Prepared statements | Every SQL query in `PatientController.php` |
| `php://input` | `store()` and `update()` methods |
| `RETURNING` clause | INSERT in `store()` |
| `rowCount()` check | `update()` and `destroy()` methods |
| camelCase transform | `formatPatient()` in `PatientController.php` |
| Response envelope | `jsonResponse()` in `PatientController.php` |
| `FETCH_ASSOC` | PDO default set in `db.php` |

---

## Further Reading

- [PHP Manual — PDO](https://www.php.net/manual/en/book.pdo.php)
- [PHP Manual — Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [GeeksforGeeks — CRUD in REST API using PHP](https://www.geeksforgeeks.org/php/crud-operation-in-rest-api-using-php/)
- [DCodeMania — RESTful API with PHP OOP and PDO](https://dcodemania.com/post/restful-api-using-php-oop-pdo)
- [PHP 8.2 Release Notes](https://www.php.net/releases/8.2/en.php)
- [OWASP — SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
