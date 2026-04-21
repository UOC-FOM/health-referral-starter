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

After installing PHP via Homebrew, make sure it is in your PATH:
```bash
echo 'export PATH="/opt/homebrew/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
php --version   # should show PHP 8.x
```

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

## Step 5 — Start the PHP Backend

Run the PHP development server from the **project root** (not inside the `backend/` folder):

```bash
php -S localhost:8000 -t backend/
```

You should see:
```
PHP 8.x.x Development Server (http://localhost:8000) started
```

Keep this terminal open. Test it is connected to your database:

```bash
curl http://localhost:8000/api/patients
```

Expected response (empty array is correct — no data yet):
```json
{ "success": true, "data": [], "message": "" }
```

If you see a PHP error like `Failed to open .env`, make sure:
- Your `.env` file is in the **project root** (same folder as `README.md`)
- You ran `php -S` from the project root, not from inside `backend/`

> **Note for Module 04:** When you reach Module 04 (Auth + JWT), you will need to run `composer install` inside the `backend/` folder to install the JWT library. You do not need Composer for Modules 01–03.

---

## Step 6 — Start the React Frontend

Open a **second terminal** (keep the PHP server running in the first):

```bash
cd frontend
npm install
npm run dev
```

You should see Vite start up and print a local URL. Open [http://localhost:5173](http://localhost:5173) in your browser.

> **Why two servers?** The React app (running in your browser) talks to the PHP app (running on your machine). They are separate processes on different ports. CORS is configured to allow this — `localhost:5173` is whitelisted in the PHP backend.

> **Note:** The `frontend/` folder will be set up in Module 05. If it doesn't exist yet, skip this step and come back when you reach Module 05.

---

## Step 7 — Verify Everything Works

At this point you should have:
- [x] Your schema exists in Supabase with 6 tables
- [x] `.env` file configured with your credentials
- [x] PHP server running at `localhost:8000`
- [x] `curl http://localhost:8000/api/patients` returns `{ "success": true, "data": [] }`

---

## Module Workflow — What to Do Each Week

Every module follows the same cycle. You will work on a **dedicated branch** for each module — this is standard professional practice and part of what you are learning.

### Step-by-Step

**1. Start a new branch for the module**

```bash
git checkout main
git pull origin main
git checkout -b module-02-php-crud
```

Name your branch `module-XX-short-description`. Always branch off the latest `main`.

**2. Read the theory note and task brief**

```
docs/theory/module-02-php-pdo.md    ← concepts explained
docs/modules/module-02.md           ← specific tasks for this module
```

**3. Implement the code**

Open the relevant files, read the TODO comments, and implement your solution.

**4. Commit your work regularly**

Don't wait until everything is done. Commit small, logical chunks as you go:

```bash
git add backend/routes/api.php
git commit -m "feat(module-02): implement route dispatcher"

git add backend/controllers/PatientController.php
git commit -m "feat(module-02): implement index and show methods"

git add backend/controllers/PatientController.php
git commit -m "feat(module-02): implement store, update, destroy"
```

**5. Push your branch to GitHub**

```bash
git push -u origin module-02-php-crud
```

The first push needs `-u` to link your local branch to the remote. After that, just `git push`.

**6. Open a Pull Request**

1. Go to your repo on GitHub
2. You will see a banner: **"Compare & pull request"** — click it
3. Set the base branch to `main`, title it e.g. `Module 02 — PHP + PDO CRUD`
4. Add a short description of what you implemented
5. Click **"Create pull request"**

Your lecturer reviews your PR, leaves comments on specific lines, and approves when complete.

**7. Merge when approved**

Once your lecturer approves, merge the PR into `main` on GitHub. Then update your local main:

```bash
git checkout main
git pull origin main
```

You are now ready to start the next module branch.

---

### Why Branches?

In professional development, no one commits directly to `main`. Changes go through branches and pull requests so that:
- Work is reviewed before it becomes part of the permanent codebase
- Multiple features can be developed in parallel without conflict
- Every change has a clear history and a reason

This course uses the same workflow you will use on the job.

---

### Commit Message Format

Use this format for all commits:

```
feat(module-02): short description of what you implemented
fix(module-02):  short description of what you fixed
docs(module-01): short description of documentation changes
```

| Prefix | When to use |
|--------|------------|
| `feat` | Adding new functionality |
| `fix` | Fixing a bug |
| `docs` | Updating comments or documentation |

Examples:
- `feat(module-01): create normalised schema with 6 tables`
- `feat(module-02): implement PatientController index and show`
- `feat(module-02): implement store with RETURNING clause`
- `fix(module-02): return 404 when rowCount is 0 on delete`
- `docs(module-01): add comments identifying NF violations`

Meaningful commit messages are part of your assessment. "update files" or "fix stuff" are not acceptable.

---

## Module Quick-Start Guide

Use this as a reference once you know the workflow. Each module builds on the previous one.

### Module 01 — Database Normalisation
```bash
git checkout -b module-01-normalisation
# Read: docs/theory/module-01-normalisation.md
# Task: database/migrations/001_initial_schema.sql + 002_indexes.sql
psql "$DB_URL" -f database/migrations/001_initial_schema.sql
psql "$DB_URL" -f database/migrations/002_indexes.sql
# Verify: Supabase Table Editor → your schema → 6 tables
```

### Module 02 — PHP + PDO CRUD
```bash
git checkout -b module-02-php-crud
# Read: docs/theory/module-02-php-pdo.md
# Tasks: backend/routes/api.php + backend/controllers/PatientController.php
php -S localhost:8000 -t backend/   # start server
curl http://localhost:8000/api/patients  # test GET
curl -X POST http://localhost:8000/api/patients \
  -H "Content-Type: application/json" \
  -d '{"name":"Your Name","postalCode":"10100"}'  # test POST
```

### Module 03 — REST API Design *(coming soon)*
```bash
git checkout -b module-03-rest-api
# Adds: DoctorController, ReferralController, nested routes
```

### Module 04 — Auth + JWT *(coming soon)*
```bash
git checkout -b module-04-auth
# Run Composer first:
cd backend && composer install && cd ..
```

### Module 05 — React Fundamentals *(coming soon)*
```bash
git checkout -b module-05-react
cd frontend && npm install && npm run dev
```

---

## Troubleshooting Common Issues

### `psql: error: connection to server failed`
- Check the `PGPASSWORD` environment variable is set
- Check the host and port in your connection string
- Check your internet connection (Supabase is a cloud database)

### `FATAL: schema "student_yourname" does not exist`
- You forgot to create the schema in Step 2, or the name doesn't match
- Go back to Supabase SQL Editor and run `CREATE SCHEMA student_yourname;`

### PHP server: `Failed to open stream: .env not found`
- Your `.env` file is missing from the project root — run `cp docs/templates/env.example .env`
- Make sure you run `php -S localhost:8000 -t backend/` from the **project root**, not from inside `backend/`

### PHP server: `PDOException: could not connect to server`
- Your `SUPABASE_DB_PASSWORD` in `.env` is wrong — double-check with your lecturer
- Your `SUPABASE_DB_SCHEMA` must match the schema you created (`student_yourname`)

### `curl` returns `{"success":false,"data":null,"message":"Failed to create patient"}`
- Check the PHP server terminal for the actual error message
- Most likely: the `postal_code` you sent doesn't exist in the `locations` table
- Insert a location first, or send `postalCode: null` (it is optional)

### React page is blank or shows `Network Error`
- The PHP server is not running — start it in Terminal 1
- Open browser devtools → Network tab → look for failed requests to `localhost:8000`

### `git status` shows `.env` as modified — should I commit it?
- **No.** Never commit `.env`. It contains your database password and JWT secret.
- If it is already tracked by git: `git rm --cached .env` then commit the removal

---

## Getting Help

1. **Read the error message** — most PHP and SQL errors tell you exactly what went wrong and on which line
2. **Check the theory note** in `docs/theory/` for the current module
3. **Check the PHP server terminal** — it logs all errors while the server is running
4. **Ask your lecturer** in the next session with a screenshot of the error and the code you wrote
5. **Open an issue** in your GitHub repo describing the problem — your lecturer can comment directly on your code
