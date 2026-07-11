# API Documentation — Phase 1

**Base URL:** `http://localhost:8000/api/v1`

All responses share a consistent envelope:

```json
{
  "success": true,
  "message": "Human readable message",
  "data": { },
  "meta": { }        // present on paginated collections
}
```

Errors:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": { "email": ["The email field is required."] }
}
```

**Authentication:** send the Sanctum token as `Authorization: Bearer <token>`.
Set header `Accept: application/json` on every request.

Status codes: `200` OK · `201` Created · `401` Unauthenticated · `403` Forbidden ·
`404` Not Found · `422` Validation Error.

---

## Authentication

| Method | Endpoint                     | Auth | Description                       |
| ------ | ---------------------------- | :--: | --------------------------------- |
| POST   | `/auth/register`             | —    | Register a new alumni member      |
| POST   | `/auth/login`                | —    | Login, returns token + user       |
| POST   | `/auth/forgot-password`      | —    | Email a password reset link       |
| POST   | `/auth/reset-password`       | —    | Reset password using token        |
| GET    | `/auth/me`                   | ✅   | Current authenticated user        |
| POST   | `/auth/logout`               | ✅   | Revoke the current token          |

### POST `/auth/register`
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+880171...",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "student_id": "CSE-1801",
  "batch": "2018",
  "department": "CSE",
  "session": "2018-2019"
}
```
→ `201` `{ data: { user, token, token_type } }`

### POST `/auth/login`
```json
{ "email": "admin@ams.test", "password": "Password123!" }
```
→ `200` `{ data: { user, token, token_type } }`

### POST `/auth/forgot-password`
```json
{ "email": "jane@example.com" }
```

### POST `/auth/reset-password`
```json
{
  "token": "<from email link>",
  "email": "jane@example.com",
  "password": "NewPass123!",
  "password_confirmation": "NewPass123!"
}
```

---

## My Profile (authenticated alumni)

| Method | Endpoint     | Auth | Description                          |
| ------ | ------------ | :--: | ------------------------------------ |
| GET    | `/profile`   | ✅   | Get the current user's profile       |
| PUT    | `/profile`   | ✅   | Update own profile (JSON)            |
| POST   | `/profile`   | ✅   | Update own profile (multipart+photo) |

Multipart upload: send `POST /profile` with `_method=PUT` and a
`profile_photo` file field alongside the text fields.

Body fields: `student_id, batch, department, session, profession, company,
designation, address, bio, profile_photo`.

---

## Alumni Directory

| Method | Endpoint             | Auth | Description                              |
| ------ | -------------------- | :--: | ---------------------------------------- |
| GET    | `/alumni`            | ✅   | Paginated directory (search + filters)   |
| GET    | `/alumni/filters`    | ✅   | Distinct filter option lists             |
| GET    | `/alumni/{id}`       | ✅   | Single alumni profile                    |
| PUT    | `/alumni/{id}`       | ✅*  | Update a profile (manager/admin)         |
| POST   | `/alumni/{id}`       | ✅*  | Update a profile (multipart + photo)     |

\* Requires `alumni.update` (Event Manager / Super Admin).

### GET `/alumni` — query parameters

| Param        | Description                                    |
| ------------ | ---------------------------------------------- |
| `search`     | Match name, email, batch, department, student_id |
| `batch`      | Filter by batch                                |
| `department` | Filter by department                           |
| `session`    | Filter by session                              |
| `profession` | Filter by profession                           |
| `sort_by`    | `batch` \| `department` \| `created_at`         |
| `sort_dir`   | `asc` \| `desc`                                 |
| `per_page`   | default 12                                      |
| `page`       | page number                                    |

Response includes `meta`: `{ current_page, per_page, total, last_page, from, to }`.

---

## User Management (Super Admin / Event Manager)

| Method | Endpoint               | Auth        | Description                    |
| ------ | ---------------------- | ----------- | ----------------------------- |
| GET    | `/users`               | admin       | Paginated users (search/filter) |
| GET    | `/users/{id}`          | admin       | Single user                   |
| POST   | `/users`               | super_admin | Create a user                 |
| PUT    | `/users/{id}`          | admin       | Update a user                 |
| PATCH  | `/users/{id}`          | admin       | Update a user                 |
| PATCH  | `/users/{id}/status`   | admin       | Change account status         |
| DELETE | `/users/{id}`          | super_admin | Soft-delete a user            |

### GET `/users` — query parameters
`search`, `status` (active/inactive/suspended), `role`, `sort_by`
(name/email/status/created_at), `sort_dir`, `per_page`, `page`.

### POST `/users`
```json
{
  "name": "New Manager",
  "email": "mgr@example.com",
  "phone": "+880...",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "status": "active",
  "roles": ["event_manager"]
}
```

### PATCH `/users/{id}/status`
```json
{ "status": "suspended" }
```

---

## Dashboard (Super Admin / Event Manager)

| Method | Endpoint                   | Auth  | Description             |
| ------ | -------------------------- | ----- | ----------------------- |
| GET    | `/dashboard/statistics`    | admin | Aggregate statistics    |

Response `data`:
```json
{
  "total_alumni": 43,
  "total_users": 46,
  "total_active_users": 40,
  "total_inactive_users": 4,
  "total_suspended_users": 2,
  "batch_distribution": [
    { "batch": "2015", "total": 6 },
    { "batch": "2016", "total": 5 }
  ]
}
```

---

## Health

| Method | Endpoint       | Description       |
| ------ | -------------- | ----------------- |
| GET    | `/api/health`  | Liveness probe    |

---

## cURL examples

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@ams.test","password":"Password123!"}'

# Authenticated request
curl http://localhost:8000/api/v1/dashboard/statistics \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"

# Directory search
curl "http://localhost:8000/api/v1/alumni?search=CSE&batch=2015&per_page=12" \
  -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```
