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

> **Tip:** On Windows, use [Git Bash](https://gitforwindows.org) as your terminal — it behaves like a Unix shell and most commands in this guide will work without modification.

> **psql is not required.** You will run all database migrations through the Supabase web dashboard (SQL Editor). No command-line database tool is needed.

---

## Step 1 — Accept the Assignment

1. Click the assignment invitation link provided by your lecturer.
2. Sign in with your **GitHub account** (create one free at [github.com](https://github.com) if you don't have one).
3. GitHub Classroom will create a private repository for you under the course organisation. It will be named something like `module-01-database-normalisation-<your-github-username>`.
4. You are the only student who can see your repo. Your lecturer can also see it to review your work.

Clone your repo to your local machine:

```bash
git clone https://github.com/UOC-FOM/<your-repo-name>.git
cd <your-repo-name>
```

---

## Step 2 — Create Your Supabase Project

You will use your own free Supabase project as your personal database. This gives you full control — you are the database admin, you can use the web-based SQL Editor, and there are no shared-credential issues.

1. Go to [supabase.com](https://supabase.com) → click **Start your project** → sign up for a free account.
2. Click **New project**. Choose any name (e.g. `health-referral`). Pick any region. Set a strong database password — **write this password down**, you will need it in Step 3.
3. Wait about 1 minute for the project to provision. You will land on the project dashboard.

That's it. You are the `postgres` admin of this project.

---

## Step 3 — Set Up Your Environment File

The application reads configuration (database host, password, API keys) from a `.env` file. This file is **never committed to git** — it stays only on your machine.

Copy the template:

```bash
cp docs/templates/env.example .env
```

Now fill in your values from the Supabase dashboard:

**Database credentials (pooler connection):**
1. In your Supabase project dashboard, click the **Connect** button (top of the page).
2. Click the **Session pooler** tab.
3. Copy the connection string. Extract the following:
   - **Host**: `aws-0-ap-southeast-1.pooler.supabase.com` (or similar — depends on your region)
   - **User**: `postgres.YOUR_PROJECT_REF` (the part before the `:`  in the connection string username)
   - **Password**: the database password you set when creating the project

**Supabase API keys (for Module 05+):**
1. Go to **Project Settings** → **API**.
2. Copy the **Project URL** and the **anon/public** key.

Your `.env` should look like:

```ini
SUPABASE_DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.abcdefghijklmn      # ← your project ref
SUPABASE_DB_PASSWORD=your_database_password
SUPABASE_DB_SCHEMA=public

VITE_SUPABASE_URL=https://abcdefghijklmn.supabase.co
VITE_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6...

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

Migrations are SQL files that create your database tables. You run them once to set up the schema. They must be run in numbered order.

You will run these directly in the Supabase **SQL Editor** — no command-line tools needed.

1. In your Supabase dashboard, click **SQL Editor** in the left sidebar → **New query**.
2. Open `database/migrations/001_initial_schema.sql` in your code editor. Select all the content and copy it.
3. Paste it into the SQL Editor query box. Click **Run**.
4. You should see a success message with no errors.
5. Repeat for `database/migrations/002_indexes.sql`: copy the full file content → paste → Run.

### Verify

1. Go to **Table Editor** in your Supabase dashboard.
2. You should see 6 tables in the `public` schema: `locations`, `patients`, `doctors`, `doctor_phones`, `referrals`, `users`.

If you see an error like `relation "patients" already exists`, the migration has already been run — that's fine, just move on to Step 5.

---

## Step 5 — Start the PHP Backend

**Windows only — enable the PostgreSQL driver first:**

PHP on Windows does not enable the `pdo_pgsql` extension by default. Without it you will see `Connection failed: could not find driver`.

1. Run `php --ini` in your terminal — it prints the path to your `php.ini` file.
2. Open that file in a text editor (e.g. Notepad).
3. Find the line `;extension=pdo_pgsql` — remove the leading `;` so it reads `extension=pdo_pgsql`.
4. Save the file and close it.

(macOS users: this extension is enabled automatically with `brew install php`.)

---

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
# macOS / Linux / Git Bash:
curl http://localhost:8000/api/patients

# Windows PowerShell:
Invoke-WebRequest -Uri http://localhost:8000/api/patients -UseBasicParsing
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
- [x] Your Supabase project created with 6 tables in the `public` schema
- [x] `.env` file configured with your own project's pooler credentials
- [x] PHP server running at `localhost:8000`
- [x] API returns `{ "success": true, "data": [] }` for patients

```bash
# macOS / Linux / Git Bash:
curl http://localhost:8000/api/patients

# Windows PowerShell:
Invoke-WebRequest -Uri http://localhost:8000/api/patients -UseBasicParsing
```

---

## Getting New Module Files (Upstream Sync)

Each module adds new starter files to the template repository. Because GitHub Classroom gave you a snapshot of the template when you accepted the assignment, **your repo does not automatically receive these new files**. You need to pull them in manually.

### Do this once — link your repo to the template

```bash
git remote add upstream https://github.com/UOC-FOM/health-referral-starter.git
```

Verify it worked:

```bash
git remote -v
# You should see both:
# origin    https://github.com/UOC-FOM/health-referral-<your-username>.git
# upstream  https://github.com/UOC-FOM/health-referral-starter.git
```

### Do this at the start of every new module

```bash
git checkout main
git fetch upstream
git merge upstream/main
git push origin main
```

This pulls the new scaffold files (e.g. `backend/` for Module 02) into your repo without touching your own work. There should be no merge conflicts because each module adds new files — it does not modify files from previous modules.

> **What if I see a merge conflict?** It means you edited a file that the template also changed (e.g. README.md). Open the file, look for the `<<<<<<<` markers, keep the content you want, then run `git add <file> && git commit`.

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
# Run both files via Supabase SQL Editor (copy-paste each file → Run)
# Verify: Supabase Table Editor → public schema → 6 tables
```

### Module 02 — PHP + PDO CRUD
```bash
git checkout -b module-02-php-crud
# Read: docs/theory/module-02-php-pdo.md
# Tasks: backend/routes/api.php + backend/controllers/PatientController.php
php -S localhost:8000 -t backend/   # start server

# macOS / Linux / Git Bash:
curl http://localhost:8000/api/patients  # test GET
curl -X POST http://localhost:8000/api/patients \
  -H "Content-Type: application/json" \
  -d '{"name":"Your Name","postalCode":"10100"}'  # test POST

# Windows PowerShell:
Invoke-WebRequest -Uri http://localhost:8000/api/patients -UseBasicParsing
```

### Module 03 — REST API Design
```bash
git checkout -b module-03-rest-api
# Read: docs/theory/module-03-rest-api.md
# Tasks: backend/routes/api.php + DoctorController.php + ReferralController.php
php -S localhost:8000 -t backend/   # start server
curl http://localhost:8000/api/doctors                   # test GET doctors
curl -X POST http://localhost:8000/api/doctors \
  -H "Content-Type: application/json" \
  -d '{"name":"Dr. Silva","specialization":"Psychiatry"}' # test POST doctor
curl http://localhost:8000/api/patients/1/referrals       # test nested route
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

### `Connection failed: could not find driver` (Windows)
- The `pdo_pgsql` PHP extension is not enabled.
- Run `php --ini`, open the listed `php.ini` file, find `;extension=pdo_pgsql`, remove the `;`, save, restart PHP server.

### `Database connection failed` or `PDOException: could not connect`
- Check `SUPABASE_DB_HOST`, `SUPABASE_DB_USER`, and `SUPABASE_DB_PASSWORD` in your `.env`.
- The host must be the **session pooler** address (starts with `aws-0-`), not the direct DB host (`db.xxx.supabase.co`).
- The user must be `postgres.YOUR_PROJECT_REF` — get this from Dashboard → Connect → Session pooler.
- Make sure you saved your database password when you created the project. If you forgot it, reset it in Dashboard → Project Settings → Database.

### `psql: command not found` or `psql is not recognized`
- psql is not required for this course. Run migrations through the Supabase SQL Editor instead (Step 4).

### PHP server: `Failed to open stream: .env not found`
- Your `.env` file is missing from the project root — run `cp docs/templates/env.example .env`
- Make sure you run `php -S localhost:8000 -t backend/` from the **project root**, not from inside `backend/`

### SQL Editor shows `relation "patients" already exists`
- The migration has already been run in this project — this is fine. Check Table Editor to confirm the 6 tables are present, then move on.

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
