# Module 01 — Database Normalisation

> **Relevant files in this project:**
> - `database/seeds/broken_table.sql` — the deliberately broken starting point
> - `database/migrations/001_initial_schema.sql` — the normalised solution
> - `database/migrations/002_indexes.sql` — performance indexes

---

## What This Module Is About

You will look at a single messy database table and, step by step, transform it into a clean, professional schema of six tables. This process is called **normalisation**.

By the end of this module you will understand:
- Why badly designed tables cause real problems
- What 1NF, 2NF, and 3NF mean — and more importantly, *why* they exist
- How primary keys and foreign keys hold a schema together
- What indexes do and when to add them

---

## 1. Start Here — The Problem With One Big Table

Imagine your hospital manager stores all referral information in a single Excel spreadsheet like this:

| patient_id | patient_name | postal_code | district | doctor_id | doctor_name | doctor_phones | referral_date | referral_reason |
|---|---|---|---|---|---|---|---|---|
| 1 | Amara Perera | 10350 | Colombo 03 | 7 | Dr. Silva | 0771234567,0779876543 | 2024-01-10 | Anxiety |
| 2 | Kasun Jayasinghe | 10350 | Colombo 03 | 7 | Dr. Silva | 0771234567,0779876543 | 2024-01-15 | Depression |
| 3 | Nimal Fernando | 20000 | Kandy | 9 | Dr. Perera | 0812345678 | 2024-01-20 | PTSD |

This looks fine at first glance — everything is in one place. But look what happens when you need to make changes:

**Problem 1 — Dr. Silva changes her name to Dr. de Silva**
You have to update every single row that mentions her. If you miss one row, your data is inconsistent — half the database says "Silva", half says "de Silva". This is an **update anomaly**.

**Problem 2 — You want to add Dr. Bandara to the system before they have any referrals**
You can't. The table has no concept of a doctor without a referral — you'd have to leave patient columns blank or insert a fake row. This is an **insert anomaly**.

**Problem 3 — Amara Perera's last referral is deleted**
Along with the referral, you lose the fact that postal code 10350 belongs to Colombo 03 — because that information only existed on that row. This is a **delete anomaly**.

> "Normalization is about structuring your data so that each fact exists in only one place. When a fact is stored in multiple places, keeping those places in sync becomes an ongoing maintenance burden — and that burden grows every time the data changes."
>
> — [FreeCodeCamp — Database Normalization 1NF 2NF 3NF Table Examples](https://www.freecodecamp.org/news/database-normalization-1nf-2nf-3nf-table-examples/)

The solution is to break this one table into several smaller, focused tables — each storing one type of fact.

---

## 2. Functional Dependency — The Core Idea

Before learning the normal form rules, you need one concept: **functional dependency**.

We say "A functionally determines B" (written **A → B**) when: knowing the value of A always tells you the value of B.

Think of it like a rule:

- `postal_code → district` — if you know the postal code is "10350", you always know the district is "Colombo 03". No exceptions.
- `doctor_id → doctor_name` — if you know doctor ID is 7, you always know the doctor's name is "Dr. Silva".
- `patient_id → patient_name` — if you know patient ID is 1, you always know the name is "Amara Perera".

The normal form rules are all based on this idea: **where should each fact live?**

> See: [DigitalOcean — Database Normalization](https://www.digitalocean.com/community/tutorials/database-normalization)

---

## 3. First Normal Form (1NF) — One Value Per Cell

**The rule:** Every column must hold a single, atomic (indivisible) value. No lists, no comma-separated values, no arrays.

### The Violation

Open `database/seeds/broken_table.sql` and look at this column:

```sql
doctor_phones TEXT,   -- stores: "0771234567,0779876543"
```

One cell is holding two phone numbers squashed together with a comma. This looks convenient but causes serious problems:

- **Can't search efficiently:** How do you find all patients whose doctor has the phone number `0779876543`? You'd have to scan every row and parse the string — slow and error-prone.
- **Can't add a third number cleanly:** You'd change an existing row rather than adding a new one.
- **No data type enforcement:** The database can't validate that each value is a valid phone number — it's just a blob of text.

### The Fix — A Separate Table

Instead of cramming all phone numbers into one cell, give each phone number its own row:

```
BEFORE (broken):
+------------+----------------------------------+
| doctor_id  | doctor_phones                    |
+------------+----------------------------------+
| 7          | 0771234567,0779876543            |  ← 1NF violation
+------------+----------------------------------+

AFTER (fixed — 1NF satisfied):
doctor_phones table:
+----+-----------+--------------+
| id | doctor_id | phone_number |
+----+-----------+--------------+
| 1  | 7         | 0771234567   |  ← one value per row
| 2  | 7         | 0779876543   |  ← one value per row
+----+-----------+--------------+
```

In `database/migrations/001_initial_schema.sql`:

```sql
CREATE TABLE doctor_phones (
    id            SERIAL PRIMARY KEY,
    doctor_id     INT  NOT NULL REFERENCES doctors(id) ON DELETE CASCADE,
    phone_number  TEXT NOT NULL
);
```

Now each row holds exactly one phone number. You can search, sort, and validate individual numbers. Adding a third number is just inserting a new row.

> See: [GeeksforGeeks — Normal Forms in DBMS](https://www.geeksforgeeks.org/dbms/normal-forms-in-dbms/)

---

## 4. Second Normal Form (2NF) — No Partial Dependencies

**The rule:** Every non-key column must depend on the **whole** primary key — not just part of it.

This only applies when your primary key is made of more than one column (a **composite key**).

In the broken referral table, imagine the primary key is the combination `(patient_id, doctor_id)` — together they identify a unique referral between a specific patient and a specific doctor. Now consider:

```sql
doctor_name VARCHAR(100)   -- which part of the key determines this?
```

Does `doctor_name` depend on `patient_id`? No. Does it depend on `doctor_id`? Yes. It only depends on **part** of the composite key. This is a **partial dependency** — a 2NF violation.

### Why This Is a Problem

Because `doctor_name` appears on every referral for that doctor, if the doctor changes their name you have to update dozens of rows. Miss one, and you have inconsistent data.

### The Fix — Move the Fact to Its Own Table

Move everything that depends only on `doctor_id` into a `doctors` table, where `doctor_id` (the `id` column) is the whole primary key:

```
BEFORE (broken):
+------------+-----------+-------------+
| patient_id | doctor_id | doctor_name |   ← doctor_name partially depends on doctor_id only
+------------+-----------+-------------+
| 1          | 7         | Dr. Silva   |
| 2          | 7         | Dr. Silva   |   ← duplicated!
| 3          | 9         | Dr. Perera  |
+------------+-----------+-------------+

AFTER (fixed — 2NF satisfied):
doctors table:              referrals table:
+----+-----------+          +----+------------+-----------+
| id | name      |          | id | patient_id | doctor_id |
+----+-----------+          +----+------------+-----------+
| 7  | Dr. Silva |          | 1  | 1          | 7         |
| 9  | Dr. Perera|          | 2  | 2          | 7         |
+----+-----------+          | 3  | 3          | 9         |
                            +----+------------+-----------+
```

Dr. Silva's name now exists in exactly one place. Changing it affects one row. This is the power of normalisation.

```sql
CREATE TABLE doctors (
    id             SERIAL PRIMARY KEY,
    name           TEXT NOT NULL,
    specialization TEXT,
    created_at     TIMESTAMPTZ DEFAULT NOW()
);
```

> See: [Medium — Database Normalization Explained with Simple Examples (2026)](https://medium.com/illumination/database-normalization-1nf-2nf-3nf-explained-with-simple-examples-6df4fd8aaa68)

---

## 5. Third Normal Form (3NF) — No Transitive Dependencies

**The rule:** Non-key columns must depend directly on the primary key — not on other non-key columns.

In the broken table:

```sql
patient_id   INT,         -- primary key
postal_code  VARCHAR(10), -- depends on patient_id (a patient has a postal code)
district     VARCHAR(50)  -- depends on postal_code, NOT directly on patient_id
```

The chain is: `patient_id → postal_code → district`

The `district` is determined by `postal_code`, which is itself a non-key column. This indirect relationship is called a **transitive dependency** — and it is a 3NF violation.

### Why This Is a Problem

Postal code "10350" always belongs to "Colombo 03". But in the broken table, this fact is repeated on every row for every patient in that area. If the district name ever changed, you'd need to update many rows — and if you missed some, you'd have patients in the same postal code appearing to be in different districts.

### The Fix — Extract the Dependency Into Its Own Table

Move the `postal_code → district` relationship into a dedicated `locations` table:

```
BEFORE (broken):
+------------+-------------+-------------+
| patient_id | postal_code | district    |  ← district depends on postal_code, not patient
+------------+-------------+-------------+
| 1          | 10350       | Colombo 03  |  ← duplicated
| 2          | 10350       | Colombo 03  |  ← duplicated
| 3          | 20000       | Kandy       |
+------------+-------------+-------------+

AFTER (fixed — 3NF satisfied):
locations table:              patients table:
+-------------+-------------+  +----+------------------+-------------+
| postal_code | district    |  | id | name             | postal_code |
+-------------+-------------+  +----+------------------+-------------+
| 10350       | Colombo 03  |  | 1  | Amara Perera     | 10350       |
| 20000       | Kandy       |  | 2  | Kasun Jayasinghe | 10350       |
+-------------+-------------+  | 3  | Nimal Fernando   | 20000       |
                               +----+------------------+-------------+
```

The district for "10350" now lives in one row of `locations`. It can never be inconsistent with itself. To find a patient's district, you join the two tables.

```sql
CREATE TABLE locations (
    postal_code TEXT PRIMARY KEY,
    district    TEXT NOT NULL
);

CREATE TABLE patients (
    id          SERIAL PRIMARY KEY,
    name        TEXT NOT NULL,
    postal_code TEXT REFERENCES locations(postal_code) ON DELETE SET NULL,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);
```

> See: [AI Diagram Maker — Database Normalization Guide](https://www.aidiagrammaker.com/blog/database-normalization-guide)

---

## 6. Primary Keys — How Rows Are Identified

A **primary key** is a column (or combination of columns) that uniquely identifies every row in a table. No two rows can share the same primary key value, and it can never be NULL.

### Two Strategies

**Surrogate key** — a system-generated identifier with no real-world meaning:
```sql
id SERIAL PRIMARY KEY
-- SERIAL is shorthand for: an auto-incrementing integer, never null, unique
-- PostgreSQL assigns 1, 2, 3, 4... automatically on each insert
```

Used for: `patients`, `doctors`, `doctor_phones`, `referrals`, `users` — entities where no natural unique identifier exists.

**Natural key** — a real-world value that is already unique and stable:
```sql
postal_code TEXT PRIMARY KEY
-- The postal code itself is a unique identifier in the real world
```

Used for: `locations` — postal codes don't change and are already unique identifiers.

### How to choose?

Use a natural key when the real-world value is guaranteed to be unique, stable, and never need to change. Use a surrogate key (SERIAL) when in doubt — patients can change their names, doctors can merge practices, but a system ID never changes.

> See: [Kerala IT Jobs — Understanding Database Normalization in PostgreSQL](https://keralait.dev/blogs/88/understanding-database-normalization-in-postgresql)

---

## 7. Foreign Keys — How Tables Are Connected

A **foreign key** is a column in one table that references the primary key of another table. It enforces **referential integrity** — the database itself prevents you from creating orphaned records.

### Example

```sql
-- In doctor_phones:
doctor_id INT NOT NULL REFERENCES doctors(id) ON DELETE CASCADE
```

This means:
- You cannot insert a phone number for `doctor_id = 99` if no doctor with `id = 99` exists
- If you delete a doctor, all their phone numbers are automatically deleted too (`ON DELETE CASCADE`)

### Cascade Behaviour Options

| Option | What happens when the parent row is deleted |
|--------|---------------------------------------------|
| `ON DELETE CASCADE` | Child rows are deleted automatically |
| `ON DELETE SET NULL` | Child column is set to NULL |
| `ON DELETE RESTRICT` | The delete is rejected if any child rows exist |
| `ON DELETE NO ACTION` | Same as RESTRICT (default) |

**In this project:**

```sql
-- If a doctor is deleted, cascade to their phone numbers
-- (a phone number without a doctor is meaningless)
doctor_id INT REFERENCES doctors(id) ON DELETE CASCADE

-- If a patient or doctor is deleted, their referrals go too
patient_id INT REFERENCES patients(id) ON DELETE CASCADE
doctor_id  INT REFERENCES doctors(id)  ON DELETE CASCADE

-- If a location is deleted, set the patient's postal code to NULL
-- (the patient still exists — we just lose their location info)
postal_code TEXT REFERENCES locations(postal_code) ON DELETE SET NULL
```

---

## 8. Indexes — Speeding Up Queries

An **index** is a separate data structure that the database maintains to allow fast lookups on a specific column — without scanning every row.

Think of it like the index at the back of a textbook. Instead of reading every page to find "PDO", you jump straight to page 47. The database does the same thing with an index.

### When to add an index

Add an index when you frequently:
- Filter rows with a `WHERE` clause on that column
- Join two tables on that column
- Sort results by that column

Primary keys are indexed automatically. Foreign key columns are **not** indexed automatically in PostgreSQL — you must add them manually.

### The cost

Every index adds overhead to `INSERT`, `UPDATE`, and `DELETE` operations, because the database must keep the index updated. For this project the data volume is small, so the overhead is negligible — but understanding the trade-off is important before adding indexes in production.

### Our Indexes

```sql
-- database/migrations/002_indexes.sql

-- Patients are often looked up by area → index postal_code
CREATE INDEX IF NOT EXISTS idx_patients_postal_code    ON patients(postal_code);

-- Doctor phones are always fetched by doctor → index the foreign key
CREATE INDEX IF NOT EXISTS idx_doctor_phones_doctor_id ON doctor_phones(doctor_id);

-- Referrals are filtered by patient and by doctor → index both foreign keys
CREATE INDEX IF NOT EXISTS idx_referrals_patient_id    ON referrals(patient_id);
CREATE INDEX IF NOT EXISTS idx_referrals_doctor_id     ON referrals(doctor_id);

-- The dashboard filters referrals by status (e.g. "show pending only")
CREATE INDEX IF NOT EXISTS idx_referrals_status        ON referrals(status);
```

> See: [PostgreSQL Normalization Best Practices — CompileNRun](https://www.compilenrun.com/docs/database/postgresql/postgresql-best-practices/postgresql-normalization/)

---

## 9. The Final Normalised Schema

After applying 1NF, 2NF, and 3NF, the single broken table becomes six clean, focused tables:

```
locations       postal_code (PK), district
    ↑
patients        id (PK), name, postal_code (FK → locations), created_at

doctors         id (PK), name, specialization, created_at
    ↓
doctor_phones   id (PK), doctor_id (FK → doctors), phone_number

referrals       id (PK), patient_id (FK → patients), doctor_id (FK → doctors),
                referral_date, reason, status, created_at

users           id (PK), email (UNIQUE), password_hash, role, created_at
```

Each table stores exactly one type of fact. No information is duplicated. Changes are made in one place. This is what normalisation achieves.

---

## 10. Quick Reference — All Three Normal Forms

| Form | Rule in plain English | Violation in our broken table | Fix |
|------|-----------------------|-------------------------------|-----|
| **1NF** | One value per cell, no lists | `doctor_phones TEXT` with comma-separated numbers | `doctor_phones` table |
| **2NF** | Every column depends on the whole key | `doctor_name` depends only on `doctor_id` | `doctors` table |
| **3NF** | Every column depends directly on the key | `district` depends on `postal_code`, not `patient_id` | `locations` table |

---

## Further Reading

- [FreeCodeCamp — Database Normalization 1NF 2NF 3NF Table Examples](https://www.freecodecamp.org/news/database-normalization-1nf-2nf-3nf-table-examples/)
- [DigitalOcean — Database Normalization](https://www.digitalocean.com/community/tutorials/database-normalization)
- [GeeksforGeeks — Normal Forms in DBMS](https://www.geeksforgeeks.org/dbms/normal-forms-in-dbms/)
- [Medium — Database Normalization Explained (2026)](https://medium.com/illumination/database-normalization-1nf-2nf-3nf-explained-with-simple-examples-6df4fd8aaa68)
- [AI Diagram Maker — Visualising 1NF, 2NF, 3NF](https://www.aidiagrammaker.com/blog/database-normalization-guide)
- [Kerala IT Jobs — Normalization in PostgreSQL](https://keralait.dev/blogs/88/understanding-database-normalization-in-postgresql)
- [PostgreSQL Best Practices — CompileNRun](https://www.compilenrun.com/docs/database/postgresql/postgresql-best-practices/postgresql-normalization/)
