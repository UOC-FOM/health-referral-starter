# Student Onboarding Guide — Health Referral System

Welcome to the Health Referral System project. Over 16 weeks you will build a complete full-stack web application from scratch — database, backend API, and frontend UI. This guide walks you through everything you need to do before writing a single line of code.

---

## What You Are Building

A web application for managing patient referrals in a Department of Psychiatry. The system allows staff (receptionists, doctors, administrators) to:
- Register patients and doctors
- Create referrals between patients and doctors
- Track referral status (pending → approved → completed)
- Log in with role-based access

**Tech Stack:**

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Database | PostgreSQL via Supabase | Stores all application data |
| Backend | PHP 8.2 + PDO | API server, business logic |
| Frontend | React 18 + Vite | User interface |
| Auth | JWT (JSON Web Tokens) | Secure login |

---

## Prerequisites — Install These Before Day 1

You need the following tools installed on your machine. Click each link for installation instructions.

### macOS

| Tool | Check if installed | Install |
|------|--------------------|---------|
| Git | `git --version` | [git-scm.com](https://git-scm.com/download/mac) |
| PHP 8.2+ | `php --version` | `brew install php` (requires [Homebrew](https://brew.sh)) |
| Composer | `composer --version` | [getcomposer.org](https://getcomposer.org/download/) |
| Node.js 18+ | `node --version` | [nodejs.org](https://nodejs.org) or `brew install node` |
| psql | `psql --version` | Included with `brew install postgresql` |

### Windows

| Tool | Install |
|------|---------|
| Git | [git-scm.com](https://git-scm.com/download/win) |
| PHP 8.2+ | [windows.php.net](https://windows.php.net/download/) — add to PATH |
| Composer | [getcomposer.org](https://getcomposer.org/download/) |
| Node.js 18+ | [nodejs.org](https://nodejs.org) |
| psql | Included with [PostgreSQL installer](https://www.postgresql.org/download/windows/) |

> **Tip:** On Windows, use [Git Bash](https://gitforwindows.org) as your terminal — it behaves like a Unix shell and all commands in this guide will work without modification.

---

## Step 1 — Accept the Assignment

1. Click the assignment invitation link provided by your lecturer.
2. Sign in with your **GitHub account** (create one free at [github.com](https://github.com) if you don't have one).
3. GitHub Classroom will create a private repository for you under the course organisation. It will be named `health-referral-<your-github-username>`.
4. You are the only student who can see your repo. Your lecturer can also see it to review your work.

Clone your repo to your local machine:

```bash
git clone https://github.com/UOC-FOM/health-referral-<your-username>.git
cd health-referral-<your-username>
```

---

## Step 2 — Create Your Personal Database Schema

All students in this course share a single PostgreSQL database hosted on Supabase. To keep your work isolated from other students, you will work in your own **schema** — a named container inside the shared database.

Think of schemas like folders: all students share the same hard drive, but each has their own folder where they cannot accidentally overwrite each other's work.

### What is a Schema?

In PostgreSQL, a schema is a namespace that contains tables, views, and other objects. By default everything goes into the `public` schema. You will create your own schema (e.g. `student_amara`) and run all your migrations there.

### How to Create Your Schema

1. Accept the Supabase invitation sent to your email by your lecturer (check your spam folder if you don't see it).
2. Open the project dashboard: **https://supabase.com/dashboard/project/ijrqtnmlfbgufuqwbjet**
3. Click **SQL Editor** in the left sidebar → click **New query**.
4. Run the following, replacing `yourname` with your first name (lowercase, no spaces, no special characters):

```sql
CREATE SCHEMA student_yourname;
```

Example: if your name is Amara, run `CREATE SCHEMA student_amara;`

5. Verify it worked: go to **Table Editor** → click the **Schema** dropdown at the top → you should see `student_amara` in the list.

> **Note:** You have access to view all schemas in this shared project. Please work only inside your own `student_yourname` schema and do not modify or delete other students' tables.

---

## Step 3 — Set Up Your Environment File

The application reads configuration (database host, password, API keys) from a `.env` file. This file is **never committed to git** — it stays only on your machine.

Copy the template:

```bash
cp docs/templates/env.example .env
```

Open `.env` in your editor and fill in the values provided by your lecturer:

```ini
# --- Database ---
SUPABASE_DB_HOST=db.ijrqtnmlfbgufuqwbjet.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=<provided by lecturer — never share this>
SUPABASE_DB_SCHEMA=student_yourname        # ← YOUR schema name here

# --- Supabase API (for React frontend) ---
VITE_SUPABASE_URL=https://ijrqtnmlfbgufuqwbjet.supabase.co
VITE_SUPABASE_ANON_KEY=<provided by lecturer>

# --- JWT (for Module 04) ---
JWT_SECRET=<generate one — see below>
JWT_EXPIRY=3600
```

### Generate Your JWT Secret

A JWT secret is a random string used to sign authentication tokens. Generate one now:

```bash
# On macOS / Linux / Git Bash:
openssl rand -hex 32
```

Copy the output (looks like `a3f9b2c1...`) and paste it as the value for `JWT_SECRET` in your `.env`.

> **Important:** Never commit your `.env` file to git. It is already listed in `.gitignore`, but double-check before committing — `git status` should never show `.env` as a file to be staged.

---

## Step 4 — Run the Database Migrations

Migrations are SQL files that create and configure your database tables. You run them once to set up your schema. They must be run in numbered order.

First, set your connection string as an environment variable (replace `yourpassword` and `student_yourname`):

```bash
export PGPASSWORD='yourpassword'
export DB_URL="postgresql://postgres@db.ijrqtnmlfbgufuqwbjet.supabase.co:5432/postgres?options=-c%20search_path%3Dstudent_yourname"
```

Then run the migrations in order:

```bash
psql "$DB_URL" -f database/migrations/001_initial_schema.sql
psql "$DB_URL" -f database/migrations/002_indexes.sql
```

You should see output like `CREATE TABLE`, `CREATE INDEX` with no errors.

### Verify in Supabase

1. Go to the Supabase dashboard → **Table Editor**
2. Select your schema from the dropdown (`student_yourname`)
3. You should see 6 tables: `locations`, `patients`, `doctors`, `doctor_phones`, `referrals`, `users`

If you see errors or the tables are missing, read the error message carefully and re-check your connection string and schema name.

---

## Step 5 — Install Backend Dependencies

The PHP backend uses one external library ([firebase/php-jwt](https://github.com/firebase/php-jwt)) for Module 04. Install it now with Composer:

```bash
cd backend
composer install
cd ..
```

This creates a `vendor/` folder inside `backend/`. This folder is gitignored — it is generated from `composer.json` and should never be committed.

---

## Step 6 — Start the Development Servers

You need two terminal windows open simultaneously.

**Terminal 1 — PHP backend (port 8000):**
```bash
php -S localhost:8000 -t backend/
```

You should see:
```
PHP 8.2.x Development Server (http://localhost:8000) started
```

**Terminal 2 — React frontend (port 5173):**
```bash
cd frontend
npm install
npm run dev
```

You should see Vite start up and print a local URL. Open [http://localhost:5173](http://localhost:5173) in your browser.

> **Why two servers?** The React app (running in your browser) talks to the PHP app (running on your machine). They are separate processes on different ports. CORS is configured to allow this — `localhost:5173` is whitelisted in the PHP backend.

---

## Step 7 — Verify Everything Works

At this point:
- [x] Your schema exists in Supabase with 6 tables
- [x] PHP server is running at `localhost:8000`
- [x] React app is running at `localhost:5173`

Test the PHP backend is responding:

```bash
curl http://localhost:8000/api/patients
```

You should get a JSON response (may be an empty array `[]` — that is correct, you have no data yet):

```json
{ "success": true, "data": [], "message": "" }
```

If you see a PHP error, check that `backend/.env` is correctly configured (the backend reads `.env` from the project root).

---

## Module Workflow — What to Do Each Week

Every module follows the same cycle:

```
1. Read docs/theory/module-XX-<topic>.md   ← understand the concepts first
2. Read docs/modules/module-XX.md          ← understand the specific tasks
3. Open the relevant files, read the TODO comments
4. Implement the code
5. Test it manually (browser + psql + curl)
6. Commit your work:
      git add <specific files>
      git commit -m "feat(module-01): create normalised schema"
      git push
```

Your lecturer reviews your repo on GitHub — they can see all your commits, so commit often with meaningful messages.

### Commit Message Format

Use this format for all commits in this course:

```
feat(module-01): short description of what you did
fix(module-02): describe what bug you fixed
```

Examples:
- `feat(module-01): create normalised schema with 6 tables`
- `feat(module-02): add PatientController with CRUD methods`
- `fix(module-03): handle missing patient_id in referral endpoint`

---

## Troubleshooting Common Issues

### `psql: error: connection to server failed`
- Check the `PGPASSWORD` environment variable is set
- Check the host and port in your connection string
- Check your internet connection (Supabase is a cloud database)

### `FATAL: schema "student_yourname" does not exist`
- You forgot to create the schema in Step 2, or the name doesn't match
- Go back to Supabase SQL Editor and run `CREATE SCHEMA student_yourname;`

### PHP server shows `Undefined index` or `PDO` errors
- Your `.env` file has missing values — check all fields are filled in
- Run `php -S localhost:8000 -t backend/` from the project root, not from inside `backend/`

### React page is blank or shows `Network Error`
- The PHP server is not running — start it in Terminal 1
- Open browser devtools → Network tab → look for failed requests to `localhost:8000`

### `git status` shows `.env` as modified — should I commit it?
- **No.** Never commit `.env`. It contains your database password and JWT secret.
- If it shows as untracked, add it to `.gitignore`. If it's already tracked, run `git rm --cached .env`

---

## Getting Help

1. **Read the error message** — most errors tell you exactly what went wrong
2. **Check the theory notes** in `docs/theory/` for the relevant module
3. **Ask your lecturer** in the next session with a screenshot of the error and the code you wrote
4. **Open an issue** in your GitHub repo describing the problem — your lecturer can comment directly on your code

---

## What's Next

Once your setup is complete, start with Module 01:

1. Read the theory note: [docs/theory/module-01-normalisation.md](theory/module-01-normalisation.md)
2. Open `database/seeds/broken_table.sql` — study the broken table
3. Open `database/migrations/001_initial_schema.sql` — read the TODO comments and implement the schema
