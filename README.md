# Issue Intake and Smart Summary System

Practical assessment project built with Laravel + Inertia React for support/operations ticket intake, triage, and tracking, including AI-assisted summaries and fallback automation.

## Requirements

To run locally, install:

- PHP `8.3+`
- Composer `2+`
- Node.js `20.19+` (or `22+`)
- npm `10+`
- MySQL `8+` (or compatible)
- Optional: Laravel Herd (for local domain + PHP runtime management)

## Setup Steps

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Prepare environment file:
   - Windows PowerShell: `Copy-Item .env.example .env`
   - macOS/Linux: `cp .env.example .env`
3. Configure `.env`:
   - database (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
   - Gemini API key (optional but recommended): `GEMINI_API_KEY`
4. Generate app key:
   - `php artisan key:generate`
5. Run migrations + seeders:
   - `php artisan migrate --seed`
6. Start the app:
   - Backend: `php artisan serve` (or use Herd)
   - Frontend: `npm run dev`
7. Open:
   - `http://localhost:8000/login` (or Herd domain, e.g. `http://sdpa.test/login`)

## Seeding Instructions

### Standard seeding (keeps existing issues)

- `php artisan db:seed`

### Fresh database with full sample data

- `php artisan migrate:fresh --seed`

Current seed behavior:

- `UserSeeder` creates 5 users (1 admin, 4 normal users).
- `CategorySeeder` creates default categories.
- `IssueSeeder` creates 50 sample issues.
- `DatabaseSeeder` only runs `IssueSeeder` when there are no existing issues.

## Seeded Login Credentials

All seeded users use password: `password`

### Admin

- `admin@admin.com`

### Normal Users

- `user1@user.com`
- `user2@user.com`
- `user3@user.com`
- `user4@user.com`

## Core Features Implemented

- Auth-required app pages with role levels (`admin` / `user`)
- Role-based issue visibility:
  - admin sees all issues
  - normal users see issues they created or are assigned to
- Issue create/list/view/update via web + API
- Category management (admin-only)
- Dashboard metrics + issue shortcuts
- Escalation rule (`requires_escalation`) for urgent/overdue issues
- AI summary generation:
  - Gemini LLM first
  - deterministic rules fallback if API unavailable/fails
  - persists `summary`, `suggested_next_action`, `summary_source`

## API Endpoints (Sanctum)

Auth:

- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`

Issues:

- `GET /api/issues`
- `POST /api/issues`
- `GET /api/issues/{id}`
- `PATCH /api/issues/{id}`

## Short Architecture and Key Decisions

- **Laravel monolith with Inertia React:** Keeps backend and UI in one codebase for faster delivery and simpler auth/session handling.
- **Service-based AI layer:** `IssueIntelligenceService` isolates LLM/rules logic from controllers and supports graceful fallback.
- **Form Request validation:** `StoreIssueRequest` and `UpdateIssueRequest` enforce consistent, centralized validation.
- **Role-aware query/access rules:** Visibility and edit permissions are enforced server-side, not only in the UI.
- **Relational schema design:** Users, issues, and categories are normalized with foreign keys to support filtering, ownership, and assignment.

## Improvements With More Time

- Add pagination, sorting, and search on issue lists.
- Introduce authorization policies (`IssuePolicy`) for cleaner permission rules.
- Add async queue job for AI generation with retry/backoff and dead-letter handling.
- Add richer activity/audit history (status, assignee, and field-level changes).
- Expand API documentation (OpenAPI) and end-to-end tests for web/API flows.
