# Health Referral System — Student Starter
### A Real-World Full-Stack Project for the University of Colombo Faculty of Medicine

[![Course](https://img.shields.io/badge/Course-UOC%20Faculty%20of%20Medicine-blue?style=for-the-badge)]()
[![Stack](https://img.shields.io/badge/Stack-PHP%20%7C%20React%20%7C%20PostgreSQL-orange?style=for-the-badge)]()
[![Companion Repo](https://img.shields.io/badge/Solution%20Repo-health--referral--solution-green?style=for-the-badge)](https://github.com/UOC-FOM/health-referral-solution)

---

## About This Repository

This is the **student starter template** for the Patient Health Referral System — a 16-week full-stack web development course taught at the University of Colombo, Faculty of Medicine.

This course uses a **hands-on, build-as-you-learn** methodology. Instead of watching tutorials, you build a real healthcare application incrementally. Each week adds a new layer to a working system — by the end, you have a complete, production-aware full-stack application.

---

## The Teaching Methodology

Most programming courses teach concepts in isolation: one week databases, next week APIs, next week frontend. Students pass exams but can't connect the pieces.

This course does the opposite:

| Traditional Course | This Course |
|-------------------|------------|
| Concepts taught in isolation | One system, built incrementally across 16 weeks |
| Generic tutorial exercises | Real healthcare domain with real data schemas |
| Passive instruction | Students write code every session |
| Final project is separate from coursework | The coursework *is* the project |

**Result:** Students graduate with a complete application they built from scratch — not just knowledge of individual concepts.

---

## What You Will Build

A complete **Patient Health Referral Management System** for a Department of Psychiatry:

- Patient registration and profile management
- Inter-department referral workflows
- Clinician authentication and role-based access
- Referral tracking and status updates
- Full audit trail for clinical compliance

This is a real system type used in hospitals. The domain is chosen deliberately — it exposes you to real data modelling challenges (normalisation, relationships, constraints) that generic tutorials don't.

---

## Tech Stack

| Layer | Technology |
|-------|----------|
| **Database** | PostgreSQL (via Supabase) |
| **Backend** | PHP 8.2 + PDO (no framework — teaches core concepts) |
| **Frontend** | React 18 + Vite |
| **Auth** | JWT |
| **Architecture** | REST API + SPA |

PHP without a framework is intentional. You learn what frameworks do *for* you before you depend on them.

---

## Course Structure

```
Module 01 — Database Design & Normalisation
Module 02 — PHP Backend & PDO CRUD
Module 03 — REST API Design
Module 04 — React Frontend Setup
Module 05 — Authentication (JWT)
Module 06 — Referral Workflow Implementation
... (16 modules total)
```

Each module has a corresponding branch in this repository. Your task each week: start from the scaffold, complete the challenge, then compare with the solution repository.

---

## Getting Started

Read **[docs/STUDENT_ONBOARDING.md](docs/STUDENT_ONBOARDING.md)** first — it covers prerequisites, Supabase setup, and running both servers.

**Quick start:**
```bash
# 1. Accept Supabase invitation from your lecturer
# 2. Create your schema
CREATE SCHEMA student_yourname;

# 3. Set up environment
cp docs/templates/env.example .env

# 4. Start servers
php -S localhost:8000 -t backend/
cd frontend && npm install && npm run dev
```

---

## Companion Repository

The **[health-referral-solution](https://github.com/UOC-FOM/health-referral-solution)** repository contains the complete working implementation. Check it *after* attempting the module yourself.

---

## About the Lecturer

Designed and taught by **Manodhya Opallage** — Full-Stack Engineer, M.Sc. Data Science (Trent University, Canada), IEEE Published.

[GitHub](https://github.com/iNVISIBLExtanx) · [LinkedIn](https://linkedin.com/in/manodhya-opallage)
