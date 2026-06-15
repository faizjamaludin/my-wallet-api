# personal-budget-api

Laravel 13 REST API for personal budget tracking. Multi-user, token-authenticated via Sanctum, PostgreSQL-backed.

**Live at: `https://my-wallet-api-6xfc.onrender.com`** (Render, free tier Docker, auto-deploys from `master`)

## Tech Stack

- **PHP 8.4** with **Laravel 13**
- **PostgreSQL** in production (Render managed DB), SQLite in-memory for tests
- **Eloquent ORM**, **Laravel Sanctum** (Bearer token auth — cookie/stateful mode intentionally disabled)
- Docker deployment via `Dockerfile` (PHP 8.4 CLI image)

## Important: Middleware

`bootstrap/app.php` does **not** call `$middleware->statefulApi()`. This is intentional — the API uses Bearer token auth only. Adding `statefulApi()` back would enable CSRF verification on API routes and break all requests from the frontend.

## Commands

```bash
# First-time setup
composer setup          # install deps, generate key, migrate, npm install & build

# Development
composer dev            # artisan serve + queue:listen + pail + vite (concurrent)

# Individual processes
php artisan serve       # API server at http://localhost:8000

# Database
php artisan migrate
php artisan migrate:fresh

# Tests
composer test           # phpunit
php artisan test

# Code style
./vendor/bin/pint       # Laravel Pint (PSR-12)
```

## Project Structure

```
app/Http/Controllers/Api/   # All API controllers
app/Models/                 # Eloquent models (User, Salary, Commitment, etc.)
database/migrations/        # Migrations
routes/api.php              # All API route definitions (no web middleware)
config/cors.php             # CORS — allows https://my-wallet.faiz-jamaludin02.workers.dev
bootstrap/app.php           # Middleware config — statefulApi() intentionally absent
Dockerfile                  # PHP 8.4 CLI, installs pdo_pgsql, runs migrations on boot
```

## Render Environment Variables

| Variable                   | Value                                                    |
|----------------------------|----------------------------------------------------------|
| `FRONTEND_URL`             | `https://my-wallet.faiz-jamaludin02.workers.dev`         |
| `SANCTUM_STATEFUL_DOMAINS` | `my-wallet.faiz-jamaludin02.workers.dev,localhost`       |
| `DB_CONNECTION`            | `pgsql`                                                  |
| `DB_HOST` / `DB_PORT`      | Render internal PostgreSQL host / 5432                   |
| `DB_DATABASE`              | `personal_budget` (or Render-assigned name)              |
| `APP_ENV`                  | `production`                                             |
| `APP_KEY`                  | generated                                                |

> If the frontend domain changes, update both `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` in the Render dashboard — Render will redeploy automatically.

## API Overview

All routes are prefixed `/api`. Protected routes require `Authorization: Bearer {token}`.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | Public | Register user |
| POST | `/api/login` | Public | Login, returns token |
| POST | `/api/auth/google` | Public | Google OAuth — **not yet implemented** |
| POST | `/api/logout` | Required | Revoke token |
| GET | `/api/dashboard/summary` | Required | Monthly financial summary |
| GET/PUT | `/api/salary` | Required | Salary & budget config |
| CRUD | `/api/commitments` | Required | Fixed/variable expenses |
| GET/POST/DELETE | `/api/credit-card/transactions` | Required | CC spending |
| GET/PUT | `/api/credit-card/budget` | Required | CC budget limit |
| CRUD | `/api/groceries` | Required | Grocery expenses |
| GET/POST/PUT | `/api/savings` | Required | Savings records |
| GET | `/api/health` | Public | Health check (used by Render) |

## Database Schema

| Table | Key Fields |
|-------|-----------|
| `users` | id, name, email, password |
| `salaries` | user_id, salary, cc_budget, grocery_budget, savings_target |
| `commitments` | user_id, name, type (fixed/variable), amount, is_paid, month (YYYY-MM) |
| `credit_card_transactions` | user_id, description, amount, category, date, month |
| `credit_card_budgets` | user_id, budget |
| `groceries` | user_id, store, amount, date, month |
| `savings` | user_id, amount, note, date, child_fund_goal, child_fund_target_date |

Month fields use `YYYY-MM` format for monthly aggregation.

## Auth Flow

1. `POST /api/register` or `POST /api/login` → returns `{ token: "..." }`
2. Include `Authorization: Bearer {token}` on all subsequent requests
3. `POST /api/logout` revokes the current token

### Google OAuth (not yet implemented — frontend is ready)

The frontend sends `POST /api/auth/google` with body `{ token: "<google-id-token>" }`.
Expected response: same shape as login — `{ token: "...", user: { id, name, email } }`.

Implementation steps:
1. Install `laravel/socialite` + `socialiteproviders/google`
2. Add `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` to Render env vars
3. Create `GoogleAuthController` that verifies the Google ID token via `Socialite::driver('google')->userFromToken($request->token)`, finds or creates the user, issues a Sanctum token, and returns it
4. Add route: `Route::post('/auth/google', [GoogleAuthController::class, 'handleToken'])` in `routes/api.php` (public, no auth middleware)

## Notes

- All protected routes are scoped to the authenticated user — no cross-user data access
- Free tier on Render spins down after inactivity; first request after sleep takes ~50s
- Debug mode should be off in production (`APP_DEBUG=false`)
- Tests use in-memory SQLite (`phpunit.xml` override) — no PostgreSQL needed to run tests locally
