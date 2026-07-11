# Alumni Event Management System (AMS) — Phase 1

A production-ready foundation for managing an alumni network: authentication,
role-based access control, user & alumni management, an alumni directory with
search/filters, and a statistics dashboard.

The repository is split into two independent applications:

```
AMS/
├── backend/     Laravel 12 REST API (PostgreSQL, Sanctum, Spatie Permission)
└── frontend/    React 19 + TypeScript + Vite + Tailwind + ShadCN UI
```

- **[backend/README.md](backend/README.md)** — API setup & clean-architecture layout
- **[frontend/README.md](frontend/README.md)** — SPA setup & structure
- **[docs/API.md](docs/API.md)** — full API endpoint reference
- **[docs/DATABASE.md](docs/DATABASE.md)** — database schema
- **[docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md)** — folder-by-folder map
- **[docs/PHASE_2.md](docs/PHASE_2.md)** — next-phase preparation

---

## Tech Stack

| Layer      | Technology                                             |
| ---------- | ------------------------------------------------------ |
| Backend    | Laravel 12, PHP 8.2+                                    |
| Database   | PostgreSQL 14+                                          |
| Auth       | Laravel Sanctum (token-based)                          |
| RBAC       | spatie/laravel-permission                               |
| Frontend   | React 19, TypeScript, Vite 6                            |
| Styling    | Tailwind CSS 3, ShadCN UI (Radix primitives)           |
| Data viz   | Recharts                                                |
| Forms      | react-hook-form + zod                                   |
| HTTP       | axios                                                   |

---

## Roles

| Role            | Key                | Capabilities                                                    |
| --------------- | ------------------ | -------------------------------------------------------------- |
| Super Admin     | `super_admin`      | Everything, incl. create/delete users                          |
| Event Manager   | `event_manager`    | View dashboard, manage users (non-admin), edit alumni profiles |
| Alumni Member   | `alumni_member`    | Browse directory, view/edit own profile                        |
| Guest           | `guest`            | Read-only directory access                                     |

---

## Quick Start

### 1. Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate

# configure PostgreSQL credentials in .env, then:
createdb ams                # or create the DB via pgAdmin
php artisan migrate --seed
php artisan storage:link
php artisan serve            # http://localhost:8000
```

### 2. Frontend

```bash
cd frontend
cp .env.example .env         # VITE_API_URL=http://localhost:8000/api/v1
npm install
npm run dev                  # http://localhost:5173
```

### Seeded demo accounts (password: `Password123!`)

| Email               | Role          |
| ------------------- | ------------- |
| admin@ams.test      | Super Admin   |
| manager@ams.test    | Event Manager |
| alumni@ams.test     | Alumni Member |

---

## Requirements

- PHP **8.2+** (Laravel 12 requirement)
- Composer 2.x
- PostgreSQL 14+
- Node.js 20+ / npm 10+
