# Database Schema — Phase 1

Engine: **PostgreSQL**. All tables use `bigint` auto-increment primary keys
unless noted. Timestamps are `timestamptz`.

## Entity Relationship (overview)

```
users (1) ───< (1) alumni_profiles
users (N) ───< model_has_roles >─── (N) roles ───< role_has_permissions >─── (N) permissions
users (1) ───< personal_access_tokens    (Sanctum)
```

---

## `users`

| Column              | Type          | Notes                                   |
| ------------------- | ------------- | --------------------------------------- |
| id                  | bigint PK     |                                         |
| name                | varchar       |                                         |
| email               | varchar       | **unique**                              |
| phone               | varchar       | nullable                                |
| email_verified_at   | timestamp     | nullable                                |
| password            | varchar       | bcrypt hash                             |
| status              | varchar       | `active` \| `inactive` \| `suspended`, indexed, default `active` |
| remember_token      | varchar       | nullable                                |
| created_at          | timestamp     |                                         |
| updated_at          | timestamp     |                                         |
| deleted_at          | timestamp     | soft delete, nullable                   |

## `alumni_profiles`

| Column         | Type       | Notes                                        |
| -------------- | ---------- | -------------------------------------------- |
| id             | bigint PK  |                                              |
| user_id        | bigint FK  | → users.id, **unique**, cascade on delete    |
| student_id     | varchar    | nullable, **unique**                         |
| batch          | varchar    | nullable, indexed (e.g. `2015`)              |
| department     | varchar    | nullable, indexed (e.g. `CSE`)               |
| session        | varchar    | nullable, indexed (e.g. `2015-2016`)         |
| profession     | varchar    | nullable, indexed                            |
| company        | varchar    | nullable                                     |
| designation    | varchar    | nullable                                     |
| address        | varchar    | nullable                                     |
| profile_photo  | varchar    | nullable (storage path)                      |
| bio            | text       | nullable                                     |
| created_at     | timestamp  |                                              |
| updated_at     | timestamp  |                                              |

Composite index: `(batch, department)` to accelerate directory queries.

## Auth / RBAC tables

- `password_reset_tokens` (email PK, token, created_at)
- `sessions` (id PK, user_id, ip_address, user_agent, payload, last_activity)
- `personal_access_tokens` — Sanctum tokens (morph to users)
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — Spatie Permission

## System tables

- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`
- `migrations`

---

## Permissions catalogue (Phase 1)

`users.view`, `users.create`, `users.update`, `users.delete`, `users.update-status`,
`alumni.view`, `alumni.update`, `alumni.delete`,
`dashboard.view`, `profile.view`, `profile.update`

### Role → permission matrix

| Permission          | Super Admin | Event Manager | Alumni | Guest |
| ------------------- | :---------: | :-----------: | :----: | :---: |
| users.view          | ✅          | ✅            |        |       |
| users.create        | ✅          |               |        |       |
| users.update        | ✅          | ✅            |        |       |
| users.delete        | ✅          |               |        |       |
| users.update-status | ✅          | ✅            |        |       |
| alumni.view         | ✅          | ✅            | ✅     | ✅    |
| alumni.update       | ✅          | ✅            |        |       |
| alumni.delete       | ✅          | ✅            |        |       |
| dashboard.view      | ✅          | ✅            |        |       |
| profile.view        | ✅          | ✅            | ✅     |       |
| profile.update      | ✅          | ✅            | ✅     |       |
