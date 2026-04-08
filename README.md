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
| 01 | Database Normalisation | — |
| 02 | PHP + PDO CRUD | — |
| 03 | REST API Design | — |
| 04 | Auth + JWT | — |
| 05 | React Fundamentals | — |
| 06 | API Integration | — |
| 07 | System Integration | — |
| 08 | Deployment + Polish | — |
