# Phase 5 — Notifications, Sponsors, Settings, Activity & Production Hardening

Final phase. Extends Phases 1–4 with a multi-channel notification system, a
sponsor module, a settings module, an activity-log audit trail, optimization,
tests, and full deployment tooling. **Additive only.**

## New dependency (backend)
`maatwebsite/excel` (Phase 4) + built-ins; no new package required for Phase 5.

## 1. Notification Module

**Channels:** Email (`mail`) · SMS (custom channel) · In-App (`database`).

**Events (notification classes, all queued + channel-aware):**
| Notification | Trigger |
| ------------ | ------- |
| `RegistrationConfirmedNotification` | Free registration or payment success |
| `PaymentSuccessNotification`        | Payment verified (`PaymentService`)  |
| `EventReminderNotification`         | `events:send-reminders` (hourly cron) |
| `EventUpdatedNotification`          | Admin edits a published event         |
| `ThankYouNotification`              | `events:send-thank-you` (daily cron)  |

- `BaseNotification` resolves channels from each user's
  `notification_preferences` (email + in-app default on; SMS opt-in + phone).
- Custom **`SmsChannel`** → `SmsService` (drivers: `log` default, `twilio`,
  `vonage`, generic `http`).
- `NotificationDispatcher` fans out to registrants in **500-row chunks**
  (memory-safe at 50k scale).
- In-app API: list, unread-count, mark-read, mark-all-read, delete. Frontend
  `NotificationBell` polls unread count every 60s.

## 2. Sponsor Module

`sponsors` (event_id, name, logo, website, amount, sponsor_type, sort_order,
is_active). Tiers: **Platinum / Gold / Silver / Bronze** (ranked display).
Admin CRUD with logo upload; sponsors are eager-loaded onto the event resource
and **rendered on the event page** (public + authenticated) grouped by tier.

## 3. Settings Module

`settings` (key, value JSON, group, is_encrypted, is_public). Groups: **site**
(name/logo/favicon), **payment** (SSLCommerz/bKash/Nagad keys), **email**, **sms**,
**theme**. Sensitive values are **encrypted at rest** (APP_KEY) and masked in the
admin API. `SettingsService` caches everything and `AppServiceProvider` overrides
runtime config (gateway keys, mail, sms, app name) from the DB — admins change
config without redeploys. Public subset served to the SPA (`SettingsContext`
applies site name, favicon, theme color).

## 4. Activity Log System

`activity_logs` (user, action, description, polymorphic subject, properties, IP,
user agent). `ActivityLogger` (never throws) records **Login, Registration,
Event Registration, Payment, Refund, Attendance, Event Update** — hooked into the
respective services. Admin audit view with action filter + search.

## 5. Optimization

| Area | Implementation |
| ---- | -------------- |
| **Redis cache** | `CACHE_STORE=redis`, session + queue on Redis (prod env) |
| **Queue workers** | Notifications/tickets queued; Supervisor config (`deploy/`) with dedicated `notifications` queue |
| **Lazy loading** | React route-level `React.lazy` + `Suspense` (verified: pages code-split into separate chunks); `Model::preventLazyLoading` in dev to catch N+1 |
| **Database indexing** | `2024_05_01_000006_add_performance_indexes` — composite indexes on hot paths (registrations, payments, attendances, events) |
| **Image optimization** | Upload size/mime limits; docs recommend S3 + CDN + on-the-fly resizing at scale |
| **Analytics caching** | Phase 4 `AnalyticsService` (Redis, TTL, flush on mutation) |

## 6. Testing

- **Unit:** `QrServiceTest` (HMAC sign/verify), `SettingsServiceTest`
  (encryption, public masking).
- **Feature/API:** `AttendanceCheckInTest` (QR check-in + duplicate prevention +
  RBAC), `SponsorTest` (CRUD + ranked public display), `NotificationTest`
  (dispatch + in-app read), `SettingsApiTest` (public/admin/RBAC).
- Run: `php artisan test` (test DB `ams_testing`).

## 7. Deployment (`deploy/` + `docs/DEPLOYMENT.md`)

Nginx (`nginx.conf`), Supervisor workers (`supervisor/ams-worker.conf`), cron
scheduler (`crontab.txt`), backup script (`backup.sh`), `.env.production.example`,
and a full deployment + scaling guide (PgBouncer, read replicas, S3/CDN, Redis).

## API Endpoints (Phase 5)

### Notifications (all authenticated users)
| Method | Endpoint | Description |
| ------ | -------- | ----------- |
| GET | `/notifications` | List (paginated, `?unread=1`) |
| GET | `/notifications/unread-count` | Unread badge count |
| PATCH | `/notifications/{id}/read` | Mark one read |
| PATCH | `/notifications/read-all` | Mark all read |
| DELETE | `/notifications/{id}` | Delete |

### Sponsors (admin) — `/admin/sponsors` (index, meta, show, store, update, destroy)
### Activity (admin) — `GET /admin/activity-logs`
### Settings — `GET /public/settings` (public) · `GET|PUT /admin/settings` (Super Admin)

## Frontend Pages (Phase 5)
- `/notifications` — in-app notifications (all users) + header `NotificationBell`
- `/admin/sponsors` — sponsor management (CRUD, logo)
- `/admin/settings` — grouped settings (Super Admin)
- `/admin/activity` — activity log audit view
- Sponsors displayed on event details (public + authenticated)

## Run

```bash
cd backend && composer install
php artisan migrate --seed          # notifications, sponsors, settings, activity tables + demo data
php artisan queue:work              # process notifications
cd ../frontend && npm run dev
```
