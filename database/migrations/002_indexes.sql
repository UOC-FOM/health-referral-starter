-- =============================================================================
-- 002_indexes.sql
-- Your task: add indexes to improve query performance on foreign key columns.
-- Run after 001_initial_schema.sql.
-- =============================================================================
-- Run: psql $SUPABASE_DB_URL -f database/migrations/002_indexes.sql
-- =============================================================================

-- -----------------------------------------------------------------------------
-- CONCEPT: Indexes speed up lookups on columns that appear in WHERE clauses
-- and JOIN conditions. Without an index, PostgreSQL scans every row in the
-- table (seq scan). With an index, it jumps directly to matching rows (index
-- scan). Foreign key columns are almost always good index candidates because
-- queries frequently filter or join on them.
--
-- Syntax reminder:
--   CREATE INDEX IF NOT EXISTS <index_name> ON <table>(<column>);
-- Use IF NOT EXISTS so re-running this file doesn't raise an error.
-- Naming convention: idx_<table>_<column>
-- -----------------------------------------------------------------------------

-- TODO: Add an index on patients(postal_code)
-- Why: queries often join patients to locations to look up the district.


-- TODO: Add an index on doctor_phones(doctor_id)
-- Why: the most common query is "give me all phone numbers for doctor X".


-- TODO: Add an index on referrals(patient_id)
-- Why: "show all referrals for this patient" is the primary dashboard query.


-- TODO: Add an index on referrals(doctor_id)
-- Why: "show all referrals assigned to this doctor" is equally common.


-- TODO: Add an index on referrals(status)
-- Why: the dashboard will frequently filter by status ('pending', 'approved', etc.)
