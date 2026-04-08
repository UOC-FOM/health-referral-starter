-- =============================================================================
-- broken_table.sql
-- A deliberately denormalized table used in Module 01 (DB Normalisation).
-- Students must identify and fix the 1NF, 2NF, and 3NF violations.
-- =============================================================================
-- DO NOT SCAFFOLD THIS FILE — copy verbatim into student starter.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- This table violates all three normal forms. Can you spot them?
--
-- Hint 1 (1NF): A column should store ONE value, not a list.
-- Hint 2 (2NF): Every non-key column must depend on the WHOLE primary key.
-- Hint 3 (3NF): Non-key columns must depend on the key — not on each other.
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS broken_patient_referral;

CREATE TABLE broken_patient_referral (
    patient_id      INT,
    patient_name    VARCHAR(100),
    postal_code     VARCHAR(10),
    district        VARCHAR(50),        -- 3NF violation: district depends on postal_code, not patient_id
    doctor_id       INT,
    doctor_name     VARCHAR(100),       -- 2NF violation: doctor_name depends only on doctor_id, not the full key
    doctor_phones   TEXT,               -- 1NF violation: stores multiple values e.g. "0771234567,0779876543"
    referral_date   DATE,
    referral_reason TEXT
);

-- -----------------------------------------------------------------------------
-- Sample data showing the violations in action:
-- -----------------------------------------------------------------------------
INSERT INTO broken_patient_referral VALUES
    (1, 'Amal Perera',   '10100', 'Colombo',   101, 'Dr. Silva',  '0771234567,0779876543', '2024-01-15', 'Anxiety disorder'),
    (2, 'Nimal Fernando', '10100', 'Colombo',   101, 'Dr. Silva',  '0771234567,0779876543', '2024-02-03', 'Depression screening'),
    (3, 'Kamala Jayawardena', '20000', 'Kandy', 102, 'Dr. Perera', '0712223344',            '2024-02-10', 'PTSD assessment'),
    (4, 'Saman Wickrama', '10100', 'Colombo',   102, 'Dr. Perera', '0712223344',            '2024-03-01', 'Stress management');

-- =============================================================================
-- YOUR TASK (Module 01):
-- =============================================================================
-- 1. Identify the exact 1NF, 2NF, and 3NF violations in this table.
-- 2. Design a normalized schema that fixes all violations.
-- 3. Implement your solution in database/migrations/001_initial_schema.sql
-- 4. Add performance indexes in database/migrations/002_indexes.sql
-- =============================================================================
