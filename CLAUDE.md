# personal-budget-api

Laravel 13 REST API for a multi-card personal finance PWA. Multi-user, token-authenticated via Sanctum, PostgreSQL-backed.

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
composer setup          # install deps, generate key, migrate

# Development
php artisan serve       # API server at http://localhost:8000

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Tests
composer test
php artisan test

# Code style
./vendor/bin/pint
```

## Project Structure

```
app/Http/Controllers/Api/
  AuthController.php
  CommitmentController.php
  CreditCardController.php     ← TO BE REPLACED by CardController + TransactionController
  DashboardController.php      ← needs rewrite for new schema
  GroceryController.php        ← TO BE REMOVED (absorbed into TransactionController)
  RuleCalculatorController.php ← TO BE REPLACED by RuleController
  SalaryController.php         ← TO BE REMOVED (income handled differently)
  SavingController.php

app/Models/
  Commitment.php
  CreditCardBudget.php         ← TO BE REMOVED
  CreditCardTransaction.php    ← TO BE REPLACED by Transaction.php
  Grocery.php                  ← TO BE REMOVED
  Salary.php                   ← TO BE REMOVED
  Saving.php
  User.php

database/migrations/
  ..._create_users_table.php
  ..._create_salaries_table.php            ← legacy, TO BE DROPPED
  ..._create_commitments_table.php         ← keep, needs due_day column added
  ..._create_credit_card_transactions_table.php  ← legacy, TO BE DROPPED
  ..._create_credit_card_budgets_table.php ← legacy, TO BE DROPPED
  ..._create_groceries_table.php           ← legacy, TO BE DROPPED
  ..._create_savings_table.php             ← keep as-is
  ..._add_payment_method_to_commitments_table.php

database/seeders/
  DatabaseSeeder.php
  CategorySeeder.php           ← TO BE CREATED (seeds preset categories)

routes/api.php
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

> If the frontend domain changes, update both `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` in the Render dashboard.

## Current Database Schema (actual)

| Table                     | Key columns                                                                         | Status         |
|---------------------------|-------------------------------------------------------------------------------------|----------------|
| `users`                   | id, name, email, password                                                           | Keep           |
| `salaries`                | id, user_id, salary, cc_budget, grocery_budget, savings_target                     | Remove         |
| `commitments`             | id, user_id, name, type, amount, is_paid, month (YYYY-MM), payment_method          | Keep + migrate |
| `credit_card_transactions`| id, user_id, description, amount, category (string), date, month                   | Replace        |
| `credit_card_budgets`     | id, user_id, budget                                                                 | Remove         |
| `groceries`               | id, user_id, description, amount, category, date, month                             | Remove         |
| `savings`                 | id, user_id, amount, note, date, child_fund_goal, child_fund_target_date            | Keep           |

## Target Database Schema

| Table          | Key columns                                                                         | Status    |
|----------------|-------------------------------------------------------------------------------------|-----------|
| `users`        | id, name, email, password                                                           | No change |
| `cards`        | id, user_id, name, type (credit/debit), last_four, credit_limit, current_balance   | **NEW**   |
| `transactions` | id, user_id, card_id, category_id, amount, date, month, description, merchant, deleted_at | **NEW** |
| `categories`   | id, user_id (null = preset), name, type (preset/custom), color, icon               | **NEW**   |
| `commitments`  | id, user_id, name, type, amount, due_day, is_paid, month                           | Update — add `due_day`, remove `payment_method` |
| `budgets`      | id, user_id, category_id, amount, month                                             | **NEW**   |
| `rules`        | id, user_id, type (70-20-10/50-30-20/custom), needs_pct, wants_pct, savings_pct   | **NEW**   |
| `savings`      | id, user_id, amount, note, date, child_fund_goal, child_fund_target_date            | No change |

### Migration notes
- `credit_card_transactions` and `groceries` → fold into `transactions` with a `card_id` foreign key and a proper `category_id`
- `salaries` and `credit_card_budgets` → remove; income is implicit (total_inflow from transactions); budgets move to per-category `budgets` table
- `commitments.payment_method` → remove (irrelevant once all spending flows through cards); add `due_day` (int 1–31)
- `transactions` uses soft deletes (`deleted_at`)
- All amounts stored as `decimal(10,2)` — never float

### Indexes (target)
- `transactions`: `(user_id, month)`, `(user_id, card_id)`, `(user_id, category_id)`, `(date)`
- `commitments`: `(user_id, month)`
- `budgets`: unique on `(user_id, category_id, month)`

### Category Seeding
- Preset categories have `user_id = null` — shared across all users
- Preset slugs: `groceries`, `entertainment`, `utilities`, `transport`, `healthcare`, `dining`, `shopping`, `subscriptions`, `fees`, `transfers`, `other`
- Run `php artisan db:seed --class=CategorySeeder` after fresh migration

## Current API Routes (actual)

```
GET  /api/health
POST /api/register
POST /api/login

# Protected (auth:sanctum)
POST   /api/logout
GET    /api/dashboard/summary
GET    /api/rule-calculator
GET    /api/salary
PUT    /api/salary
GET    /api/commitments
POST   /api/commitments
PUT    /api/commitments/{id}
DELETE /api/commitments/{id}
PATCH  /api/commitments/{id}/toggle-paid
GET    /api/credit-card/transactions
POST   /api/credit-card/transactions
DELETE /api/credit-card/transactions/{id}
GET    /api/credit-card/budget
PUT    /api/credit-card/budget
GET    /api/groceries
POST   /api/groceries
DELETE /api/groceries/{id}
GET    /api/savings/summary
PUT    /api/savings/child-fund
GET    /api/savings
POST   /api/savings
PUT    /api/savings/{id}
DELETE /api/savings/{id}
```

## Target API Routes

### Auth
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | Public | Register |
| POST | `/api/login` | Public | Login, returns token |
| POST | `/api/auth/google` | Public | Google OAuth — **not yet implemented** |
| POST | `/api/logout` | Required | Revoke token |

### Cards
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/cards` | Required | List user's cards |
| POST | `/api/cards` | Required | Add card |
| PUT | `/api/cards/{id}` | Required | Update card |
| DELETE | `/api/cards/{id}` | Required | Delete card |

### Transactions
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/transactions` | Required | List (`?month=`, `?card_id=`, `?category_id=`, `?view=daily\|weekly\|monthly`) |
| POST | `/api/transactions` | Required | Add transaction |
| PUT | `/api/transactions/{id}` | Required | Update transaction |
| DELETE | `/api/transactions/{id}` | Required | Soft delete |
| POST | `/api/transactions/import` | Required | CSV import (max 5 MB, multipart/form-data) |

### Categories
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/categories` | Required | List all (preset + user custom) |
| POST | `/api/categories` | Required | Create custom category |
| PUT | `/api/categories/{id}` | Required | Update custom category |
| DELETE | `/api/categories/{id}` | Required | Delete custom category |

### Commitments
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/commitments` | Required | List (`?month=YYYY-MM`) |
| POST | `/api/commitments` | Required | Add |
| PUT | `/api/commitments/{id}` | Required | Update (incl. toggle is_paid) |
| DELETE | `/api/commitments/{id}` | Required | Delete |

### Budgets
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/budgets` | Required | Get for a month (`?month=`) |
| POST | `/api/budgets` | Required | Set/update for a category+month |

### Financial Rules
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/rules` | Required | Get active rule config |
| PUT | `/api/rules` | Required | Set/update rule |

### Savings
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/savings` | Required | List entries |
| POST | `/api/savings` | Required | Add entry |
| PUT | `/api/savings/{id}` | Required | Update entry |
| DELETE | `/api/savings/{id}` | Required | Delete entry |
| GET | `/api/savings/summary` | Required | Totals + child fund info |
| PUT | `/api/savings/child-fund` | Required | Update child fund goal |

### Dashboard
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/dashboard/summary` | Required | Aggregated monthly snapshot (`?month=YYYY-MM`) |

### Health
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/health` | Public | Health check (used by Render) |

## Dashboard Summary Response (target)

```json
{
  "month": "2026-06",
  "total_inflow": 0,
  "total_outflow": 4250.00,
  "net_position": -4250.00,
  "by_card": [
    { "card_id": 1, "name": "Maybank CC", "spent": 2100.00, "limit": 5000.00, "utilization_pct": 42 }
  ],
  "by_category": [
    { "category_id": 1, "name": "Groceries", "spent": 450.00, "budget": 600.00 }
  ],
  "commitments_paid": 3,
  "commitments_unpaid": 1,
  "commitments_due_soon": 1,
  "savings_this_month": 200.00,
  "total_savings": 3400.00,
  "rule_health": { "type": "70-20-10", "needs_pct": 68, "wants_pct": 22, "savings_pct": 10 }
}
```

## CSV Import

`POST /api/transactions/import` — `multipart/form-data`, field `file` (CSV, max 5 MB), optional `card_id`.
Expected CSV columns: `date`, `amount`, `description`, `merchant`, `category` (matched by name, falls back to `Other`).
Returns `{ imported: N, skipped: N, errors: [...] }`.

## Auth Flow

1. `POST /api/register` or `/api/login` → `{ token: "..." }`
2. `Authorization: Bearer {token}` on all subsequent requests
3. `POST /api/logout` revokes the token

### Google OAuth (not yet implemented — frontend is ready)

Frontend sends `POST /api/auth/google` with `{ token: "<google-id-token>" }`.
Expected response: `{ token: "...", user: { id, name, email } }`.

Steps to implement:
1. Install `laravel/socialite` + `socialiteproviders/google`
2. Add `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` to Render env
3. `GoogleAuthController` — verify via `Socialite::driver('google')->userFromToken($request->token)`, findOrCreate user, issue Sanctum token
4. Add public route in `routes/api.php`

## Notes

- All protected routes are scoped to the authenticated user — no cross-user data access
- Free tier on Render spins down after inactivity; first request after sleep ~50s
- `APP_DEBUG=false` in production
- Tests use in-memory SQLite — no PostgreSQL needed locally
- Soft deletes on `transactions` — `withTrashed()` only for audit; never expose in normal queries
- Amounts always `decimal(10,2)` — never float
