# AMS Frontend — React 19 + TypeScript

Single-page app for the Alumni Event Management System. Built with Vite,
Tailwind CSS, and ShadCN UI (Radix primitives).

## Requirements

- Node.js 20+
- npm 10+
- The backend API running at `http://localhost:8000`

## Setup

```bash
cp .env.example .env      # set VITE_API_URL=http://localhost:8000/api/v1
npm install
npm run dev               # http://localhost:5173
```

## Scripts

| Command             | Description                        |
| ------------------- | ---------------------------------- |
| `npm run dev`       | Start the dev server (HMR)         |
| `npm run build`     | Type-check + production build      |
| `npm run preview`   | Preview the production build       |
| `npm run lint`      | Run ESLint                         |
| `npm run type-check`| `tsc --noEmit`                     |

## Structure

- `src/lib/api.ts` — axios instance, bearer-token interceptor, 401 auto-logout.
- `src/services/*` — typed API wrappers (auth, user, alumni, dashboard).
- `src/context/AuthContext.tsx` — session state, `login/register/logout`,
  `hasRole` / `hasPermission` helpers, exposed via `useAuth()`.
- `src/components/ui/*` — ShadCN primitives.
- `src/components/common/*` — app-specific shared components (route guards,
  cards, forms, pagination).
- `src/layouts/*` — `AuthLayout` (split-screen auth) and `DashboardLayout`
  (sidebar shell with role-filtered nav).
- `src/pages/*` — feature pages.

## Routing & access

| Route            | Access                         |
| ---------------- | ------------------------------ |
| `/login` etc.    | Guests only                    |
| `/`              | Dashboard (admins) / redirect  |
| `/directory`     | Any authenticated user         |
| `/profile`       | Any authenticated user         |
| `/profile/edit`  | Any authenticated user         |
| `/users`         | Super Admin / Event Manager    |
| `/alumni`        | Super Admin / Event Manager    |

Role gating is enforced by `ProtectedRoute` on the client **and** by the API on
the server — the client checks are UX only.

## Features

- **Auth:** login, register, forgot & reset password (deep-linked from email).
- **Dashboard:** total alumni, active users, batch distribution (Recharts).
- **User Management:** searchable/filterable table, create/edit dialog, status
  change, delete (Super Admin).
- **Alumni Directory:** search by name/batch/department, filter by session &
  profession, card grid, pagination.
- **Alumni Management:** admin table with inline profile editing.
- **Profile:** view & edit own profile incl. photo upload.
