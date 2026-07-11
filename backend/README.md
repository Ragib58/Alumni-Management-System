# AMS Backend — Laravel 12 API

REST API for the Alumni Event Management System. Built with clean-architecture
layering: **Controller → Service → Repository (interface) → Model**.

## Requirements

- PHP **8.2+**
- Composer 2.x
- PostgreSQL 14+

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Configure the PostgreSQL section of `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ams
DB_USERNAME=postgres
DB_PASSWORD=postgres

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

Create the database, migrate and seed:

```bash
createdb ams                 # or via pgAdmin
php artisan migrate --seed
php artisan storage:link     # serve uploaded profile photos
php artisan serve            # http://localhost:8000
```

## Seeded accounts (password: `Password123!`)

| Email            | Role          |
| ---------------- | ------------- |
| admin@ams.test   | Super Admin   |
| manager@ams.test | Event Manager |
| alumni@ams.test  | Alumni Member |

Plus ~46 generated alumni for the directory & dashboard stats.

## Tests

```bash
# create a separate test DB first: createdb ams_testing
php artisan test
```

## Architecture layers

| Layer          | Location                        | Responsibility                        |
| -------------- | ------------------------------- | ------------------------------------- |
| Route          | `routes/api.php`                | URL → controller, middleware gates    |
| Middleware     | `app/Http/Middleware`           | Sanctum auth, active-account, RBAC    |
| Controller     | `app/Http/Controllers/Api/V1`   | HTTP orchestration only               |
| FormRequest    | `app/Http/Requests`             | Input validation                      |
| Policy         | `app/Policies`                  | Authorization rules                   |
| Service        | `app/Services`                  | Business logic, transactions          |
| Repository     | `app/Repositories`              | Persistence behind interfaces         |
| Resource       | `app/Http/Resources`            | Response shaping                      |
| Model          | `app/Models`                    | Eloquent entities & scopes            |

Repository contracts are bound to Eloquent implementations in
`app/Providers/RepositoryServiceProvider.php`.

## Key conventions

- **Response envelope** via `App\Traits\ApiResponse`: every JSON response has
  `success`, `message`, `data`, and `meta` (for pagination).
- **RBAC** via `spatie/laravel-permission`. Roles:
  `super_admin`, `event_manager`, `alumni_member`, `guest`.
- **Auth** via Laravel Sanctum bearer tokens.
- **Soft deletes** on users.
- Profile photo uploads are stored on the `public` disk under `avatars/`.

See [../docs/API.md](../docs/API.md) for the full endpoint reference.
