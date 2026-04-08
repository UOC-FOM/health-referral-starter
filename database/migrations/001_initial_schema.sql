-- =============================================================================
-- 001_initial_schema.sql
-- Your task: design and implement a normalized schema that fixes all the
-- violations you identified in database/seeds/broken_table.sql.
-- =============================================================================
-- Run: psql $SUPABASE_DB_URL -f database/migrations/001_initial_schema.sql
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Drop tables in reverse dependency order (safe dev reset — do not change)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS doctor_phones;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS locations;

-- -----------------------------------------------------------------------------
-- CONCEPT: 3NF fix — the district column in the broken table depended on
-- postal_code, not on the patient. Extract it into its own table where
-- postal_code is the natural primary key (no surrogate id needed).
-- -----------------------------------------------------------------------------

-- TODO: CREATE TABLE locations
-- Columns needed:
--   postal_code  → natural primary key — TEXT, not INT (postal codes can have leading zeros)
--   district     → the district name, cannot be empty


-- -----------------------------------------------------------------------------
-- CONCEPT: patients table — the core entity.
-- The postal_code column becomes a foreign key pointing to locations,
-- enforcing referential integrity (you can't register a patient with an
-- unknown postal code).
-- -----------------------------------------------------------------------------

-- TODO: CREATE TABLE patients
-- Columns needed:
--   id           → auto-incrementing primary key (hint: SERIAL)
--   name         → patient full name, cannot be empty
--   postal_code  → links to locations(postal_code) (hint: REFERENCES)
--                  use ON DELETE SET NULL so deleting a location doesn't delete patients
--   created_at   → auto-filled timestamp (hint: TIMESTAMPTZ DEFAULT NOW())


-- -----------------------------------------------------------------------------
-- CONCEPT: 2NF fix — doctor_name in the broken table depended only on
-- doctor_id, not on the full row. Extract doctors into their own table.
-- specialization is nullable — not every doctor record will have one.
-- -----------------------------------------------------------------------------

-- TODO: CREATE TABLE doctors
-- Columns needed:
--   id             → auto-incrementing primary key
--   name           → doctor full name, cannot be empty
--   specialization → area of specialty, optional (nullable)
--   created_at     → auto-filled timestamp


-- -----------------------------------------------------------------------------
-- CONCEPT: 1NF fix — doctor_phones stored "0771234567,0779876543" in one cell.
-- Each phone number becomes its own row linked to the doctor by a foreign key.
-- ON DELETE CASCADE: if a doctor is deleted, their phone records go with them.
-- -----------------------------------------------------------------------------

-- TODO: CREATE TABLE doctor_phones
-- Columns needed:
--   id           → auto-incrementing primary key
--   doctor_id    → foreign key to doctors(id), NOT NULL
--                  (hint: ON DELETE CASCADE)
--   phone_number → the phone number, cannot be empty


-- -----------------------------------------------------------------------------
-- CONCEPT: referrals — the join between patients and doctors, plus clinical
-- metadata. status must be one of three known values; use a CHECK constraint
-- to enforce this at the database level (not just in application code).
-- ON DELETE CASCADE on both FKs: removing a patient or doctor removes their
-- referrals automatically.
-- -----------------------------------------------------------------------------

-- TODO: CREATE TABLE referrals
-- Columns needed:
--   id            → auto-incrementing primary key
--   patient_id    → FK to patients(id), NOT NULL, ON DELETE CASCADE
--   doctor_id     → FK to doctors(id), NOT NULL, ON DELETE CASCADE
--   referral_date → date of referral (DATE type)
--   reason        → free-text clinical reason, optional
--   status        → one of: 'pending', 'approved', 'completed'
--                   (hint: DEFAULT 'pending', CHECK constraint)
--   created_at    → auto-filled timestamp


-- -----------------------------------------------------------------------------
-- CONCEPT: users — authentication table used from Module 04 onwards.
-- Passwords are ALWAYS stored as bcrypt hashes — never plaintext.
-- role must be constrained to known values using a CHECK constraint.
-- -----------------------------------------------------------------------------

-- TODO: CREATE TABLE users
-- Columns needed:
--   id            → auto-incrementing primary key
--   email         → unique, cannot be empty (hint: UNIQUE NOT NULL)
--   password_hash → bcrypt hash — never the raw password, cannot be empty
--   role          → one of: 'admin', 'doctor', 'receptionist'
--                   (hint: CHECK constraint)
--   created_at    → auto-filled timestamp
