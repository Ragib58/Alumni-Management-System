# Phase 2 — Preparation & Roadmap

Phase 1 delivered the platform foundation: auth, RBAC, users, alumni profiles,
directory, and dashboard. Phase 2 builds the **event management** domain on top
of it.

## Planned features

### 1. Events
- `events` table: title, slug, description, cover_image, venue, address,
  starts_at, ends_at, capacity, status (draft/published/cancelled/completed),
  is_paid, ticket_price, created_by.
- Event CRUD for Event Managers & Super Admins.
- Public/published event listing with search & filters (date, batch-targeted).

### 2. Registrations / RSVP
- `event_registrations`: event_id, user_id, status (registered/waitlisted/
  cancelled/attended), guests_count, registered_at, checked_in_at.
- Capacity enforcement + waitlist.
- QR-code check-in for attendance.

### 3. Ticketing & Payments (optional paid events)
- `tickets`, `payments` (gateway: SSLCommerz/Stripe), invoice generation.

### 4. Notifications
- Email + in-app notifications (event reminders, registration confirmations).
- `notifications` table already available via Laravel; add broadcast (Reverb/
  Pusher) for real-time.

### 5. Media & Gallery
- Event photo galleries; move uploads to S3 (`FILESYSTEM_DISK=s3`).

### 6. Reporting
- Attendance reports, revenue reports, per-batch engagement analytics.

## Suggested new tables

```
events                (id, title, slug, description, venue, starts_at, ends_at,
                       capacity, status, is_paid, ticket_price, created_by, ...)
event_registrations   (id, event_id, user_id, status, guests_count,
                       registered_at, checked_in_at)
tickets               (id, event_id, registration_id, code, price, issued_at)
payments              (id, user_id, event_id, amount, gateway, txn_id, status)
event_media           (id, event_id, path, type, caption)
```

## New permissions to add

`events.view`, `events.create`, `events.update`, `events.delete`,
`events.publish`, `registrations.view`, `registrations.manage`,
`payments.view`, `reports.view`.

## Architecture notes (reuse Phase 1 patterns)

- Add `EventRepositoryInterface` + `EventRepository`, `EventService`,
  `EventController`, `EventPolicy`, `EventResource` — mirroring the existing
  Alumni slice.
- Keep the same response envelope (`ApiResponse` trait) and `/api/v2` or extend
  `/api/v1`.
- Frontend: add `services/event.service.ts`, `pages/events/*`, and event nav
  items gated by the new permissions.

## Technical debt / hardening for Phase 2

- Add rate limiting on auth endpoints (`throttle:6,1`).
- Email verification flow (Sanctum + `verified` middleware).
- Full test coverage (Feature tests per endpoint; currently `AuthTest` seeded).
- API docs via OpenAPI/Scribe generation.
- CI pipeline (Pint + PHPUnit + tsc + eslint).
- Move file storage to S3 and add image resizing.
