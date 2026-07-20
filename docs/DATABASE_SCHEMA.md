# Database Schema — Complete (All Phases)

Engine: **PostgreSQL**. `id` = `bigint` PK auto-increment; timestamps =
`timestamptz` unless noted. Bracketed labels: **UK** unique, **FK** foreign key,
**IX** indexed.

## Phase 1 — Auth, Users, Alumni, RBAC

### users
`id` · name · email **UK** · phone · email_verified_at · password ·
status **IX** (active/inactive/suspended) · **notification_preferences** json
*(P5)* · remember_token · timestamps · deleted_at (soft delete)

### alumni_profiles
`id` · user_id **FK,UK** · student_id **UK** · batch **IX** · department **IX** ·
session **IX** · profession **IX** · company · designation · address ·
profile_photo · bio · timestamps · **IX(batch, department)**

### RBAC (Spatie)
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`,
`role_has_permissions`.

### System
`password_reset_tokens`, `sessions`, `personal_access_tokens` (Sanctum),
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`.

## Phase 2 — Events

### events
`id` · title · slug **UK** · banner · description · venue · type **IX**
(reunion/seminar/workshop/sports/cultural_program/iftar) · event_date **IX** ·
registration_start · registration_end · fee `decimal(10,2)` · max_capacity ·
status **IX** (draft/published/closed/completed) · created_by **FK** · timestamps ·
deleted_at · **IX(status, event_date)** *(P5)*

### event_form_fields
`id` · event_id **FK** · label · name · type · options json · is_required ·
placeholder · help_text · sort_order · timestamps · **UK(event_id, name)**

### event_registrations
`id` · registration_no **UK** · event_id **FK** · user_id **FK** · status **IX**
(pending/confirmed/cancelled) · payment_status **IX** · amount `decimal(10,2)` ·
form_response json · registered_at · cancelled_at · timestamps ·
**UK(event_id, user_id)** · **IX(event_id, status)**, **IX(user_id, status)**,
**IX(created_at)** *(P5)*

## Phase 3 — Payments & Tickets

### payments
`id` · registration_id **FK** · transaction_id **UK** · gateway_transaction_id **IX** ·
amount `decimal(10,2)` · currency · gateway **IX** (sslcommerz/bkash/nagad) ·
status **IX** (pending/paid/failed/refunded) · payment_date · meta json ·
timestamps · **IX(gateway, status)**, **IX(status, payment_date)** *(P5)*

### tickets
`id` · registration_id **FK,UK** · ticket_no **UK** · qr_token **UK(64)** ·
qr_signature · pdf_path · issued_at · emailed_at · checked_in_at · timestamps

## Phase 4 — Attendance

### attendances
`id` · registration_id **FK,UK** · event_id **FK** · status **IX**
(not_arrived/checked_in/checked_out) · checkin_time · checkout_time ·
checked_by **FK** · timestamps · **IX(event_id, status)**, **IX(checkin_time)** *(P5)*

## Phase 5 — Notifications, Sponsors, Settings, Activity

### notifications  (Laravel database channel)
`id` uuid PK · type · notifiable_type + notifiable_id (morph) · data text ·
read_at · timestamps · **IX(notifiable_type, notifiable_id, read_at)**

### sponsors
`id` · event_id **FK** · name · logo · website · amount `decimal(12,2)` ·
sponsor_type **IX** (platinum/gold/silver/bronze) · sort_order · is_active ·
timestamps · **IX(event_id, sponsor_type)**

### settings
`id` · key **UK** · value json · group **IX** (site/payment/email/sms/theme) ·
is_encrypted · is_public · timestamps

### activity_logs
`id` · user_id **FK** · action **IX** · description · subject_type + subject_id
(morph) · properties json · ip_address · user_agent · created_at **IX** ·
**IX(user_id, action)**

---

## Referential actions
- `alumni_profiles.user_id`, `event_form_fields.event_id`,
  `event_registrations.*`, `payments.registration_id`, `tickets.registration_id`,
  `attendances.*`, `sponsors.event_id` → **ON DELETE CASCADE**.
- `events.created_by`, `attendances.checked_by`, `activity_logs.user_id` →
  **ON DELETE SET NULL**.
- `users` uses **soft deletes** (recoverable).

## Migration order
```
0001_01_01_*                      users, cache, jobs
2024_01_01_*                      sanctum tokens, permission tables, alumni_profiles
2024_02_01_*                      events, event_form_fields, event_registrations
2024_03_01_*                      payments, tickets
2024_04_01_*                      attendances
2024_05_01_000001..000006         notifications, sponsors, settings, activity_logs,
                                  user notification_preferences, performance indexes
```
