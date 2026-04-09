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

Read the full setup guide first: **[docs/STUDENT_ONBOARDING.md](docs/STUDENT_ONBOARDING.md)**

It covers prerequisites, creating your Supabase schema, configuring `.env`, running migrations, and starting the dev servers.

Quick reference:
1. Accept the Supabase invitation from your lecturer
2. Create your schema: `CREATE SCHEMA student_yourname;` in Supabase SQL Editor
3. `cp docs/templates/env.example .env` — fill in your credentials
4. Run migrations against your schema
5. `php -S localhost:8000 -t backend/` — start PHP server
6. `cd frontend && npm install && npm run dev` — start React

## Git Workflow

Each module is developed on its own branch and merged via a Pull Request. **Never commit directly to `main`.**

```bash
# Start of every module
git checkout main && git pull origin main
git checkout -b module-01-normalisation

# During work — commit often
git add <files>
git commit -m "feat(module-01): describe what you did"
git push -u origin module-01-normalisation

# When done — open a Pull Request on GitHub for lecturer review
```

See [docs/STUDENT_ONBOARDING.md](docs/STUDENT_ONBOARDING.md) for the full workflow.

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
