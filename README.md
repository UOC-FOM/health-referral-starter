# Health Referral System — Student Starter

This is the starter template for the **Patient Health Referral System** project, part of a 16-week full-stack web development course.

## Your Task

You will build a complete full-stack application for managing patient referrals in a Department of Psychiatry. Each module introduces new concepts and extends the system.

## Tech Stack

- **Database:** PostgreSQL (Supabase)
- **Backend:** PHP 8.2 + PDO (no framework)
- **Frontend:** React 18 + Vite
- **Auth:** JWT

## Getting Started

1. Copy the environment template:
   ```bash
   cp docs/templates/env.example .env
   ```
2. Fill in your Supabase credentials in `.env`
3. Run the database migrations:
   ```bash
   psql $SUPABASE_DB_URL -f database/migrations/001_initial_schema.sql
   psql $SUPABASE_DB_URL -f database/migrations/002_indexes.sql
   ```
4. Start the backend:
   ```bash
   php -S localhost:8000 -t backend/
   ```
5. Start the frontend:
   ```bash
   cd frontend && npm install && npm run dev
   ```

## Module Progress

Each module has a brief in `docs/modules/`. Read the brief before starting each module.

| Module | Topic | Status |
|--------|-------|--------|
| **01** | **Database Normalisation** | **← current** |
| 02 | PHP + PDO CRUD | — |
| 03 | REST API Design | — |
| 04 | Auth + JWT | — |
| 05 | React Fundamentals | — |
| 06 | API Integration | — |
| 07 | System Integration | — |
| 08 | Deployment + Polish | — |

---

## Module 01 — Database Normalisation

**Concepts:** 1NF, 2NF, 3NF, Primary Keys, Foreign Keys, Indexes

### The Challenge

Open `database/seeds/broken_table.sql`. You'll find a single denormalized table called `broken_patient_referral` that violates all three normal forms:

| Violation | Column | Problem |
|-----------|--------|---------|
| **1NF** | `doctor_phones` | Stores multiple phone numbers in one cell (comma-separated) |
| **2NF** | `doctor_name` | Depends only on `doctor_id`, not the full row |
| **3NF** | `district` | Depends on `postal_code`, not on the patient |

### Your Tasks

1. **Identify** the violations — write a comment above each column explaining the problem
2. **Design** a normalized schema that fixes all three violations
3. **Implement** your solution in `database/migrations/001_initial_schema.sql`
4. **Add indexes** in `database/migrations/002_indexes.sql`
5. **Verify** your schema in the Supabase dashboard (Table Editor)

### Expected Schema (6 tables)

```
locations       (postal_code PK, district)
patients        (id, name, postal_code FK → locations, created_at)
doctors         (id, name, specialization, created_at)
doctor_phones   (id, doctor_id FK → doctors, phone_number)
referrals       (id, patient_id FK, doctor_id FK, referral_date, reason, status, created_at)
users           (id, email, password_hash, role, created_at)
```

### Assessment Checklist
- [ ] All 6 tables created with correct column types
- [ ] All foreign keys include `ON DELETE CASCADE` (or `SET NULL` where appropriate)
- [ ] `status` column uses a `CHECK` constraint
- [ ] `email` column is `UNIQUE NOT NULL`
- [ ] All 5 indexes created in `002_indexes.sql`
- [ ] Schema verified in Supabase Table Editor
