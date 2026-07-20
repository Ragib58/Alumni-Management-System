# API Reference — Consolidated (All Phases)

**Base URL:** `/api/v1`  ·  **Auth:** `Authorization: Bearer <token>` (Sanctum)
·  **Headers:** `Accept: application/json`

**Response envelope**
```json
{ "success": true, "message": "…", "data": …, "meta": { } }
```
Errors: `{ "success": false, "message": "…", "errors": { "field": ["…"] } }`
Codes: 200 · 201 · 401 · 403 · 404 · 422.

Roles: `super_admin` · `event_manager` · `alumni_member` · `guest`.
"admin" below = Super Admin **or** Event Manager unless stated.

---

## Public (no auth)
| Method | Endpoint | Description |
| ------ | -------- | ----------- |
| POST | `/auth/register` | Register (alumni) |
| POST | `/auth/login` | Login → token |
| POST | `/auth/forgot-password` · `/auth/reset-password` | Password reset |
| GET | `/public/events` · `/public/events/{slug}` | Published events (+ sponsors) |
| GET | `/public/settings` | Site/theme public settings |
| GET\|POST | `/public/payments/{gateway}/return` | Live gateway browser return |
| POST | `/public/payments/{gateway}/ipn` | Live gateway IPN |
| GET | `/health` | Liveness probe |

## Session & Profile (auth)
| Method | Endpoint |
| ------ | -------- |
| GET `/auth/me` · POST `/auth/logout` |
| GET\|PUT\|POST `/profile` | own alumni profile (+photo) |

## Notifications (auth — all users)
`GET /notifications` · `GET /notifications/unread-count` ·
`PATCH /notifications/{id}/read` · `PATCH /notifications/read-all` ·
`DELETE /notifications/{id}`

## Alumni Directory (auth)
`GET /alumni` (search/filter) · `GET /alumni/filters` · `GET /alumni/{id}` ·
`PUT\|POST /alumni/{id}` (admin edit)

## Events (auth)
`GET /events` · `GET /events/meta` · `GET /events/slug/{slug}`
Admin: `GET /events/{id}` · `POST /events` · `PUT\|POST /events/{id}` ·
`DELETE /events/{id}`

## Registrations (auth)
`GET /my-registrations` · `GET /my-registrations/{id}` ·
`POST /events/{event}/register` · `DELETE /registrations/{id}/cancel`
Admin: `GET /registrations` · `GET /events/{event}/registrations` ·
`GET /registrations/{id}` · `PATCH /registrations/{id}/status`

## Payments (auth)
`POST /registrations/{id}/pay` · `POST /payments/{id}/sandbox-complete` ·
`GET /payments/{id}` · `GET /my-payments`
Admin: `GET /admin/payments` · `GET /admin/payments/{id}` ·
`GET /admin/payments-revenue` · `POST /admin/payments/{id}/refund`

## Tickets (auth)
`GET /my-tickets` · `GET /tickets/{id}` · `GET /tickets/{id}/download` ·
`POST /tickets/{id}/email`

## Attendance (admin)
`POST /admin/attendance/check-in` · `POST /admin/attendance/check-out` ·
`GET /admin/events/{event}/attendance` · `GET /admin/events/{event}/attendance/stats`

## Analytics & Reports (admin)
`GET /admin/analytics/dashboard` · `GET /admin/analytics/year-comparison` ·
`GET /admin/reports/{event|financial|alumni}` ·
`GET /admin/reports/{type}/export/{excel|csv|pdf}`

## Sponsors (admin)
`GET /admin/sponsors` · `GET /admin/sponsors/meta` · `GET /admin/sponsors/{id}` ·
`POST /admin/sponsors` · `PUT\|POST /admin/sponsors/{id}` · `DELETE /admin/sponsors/{id}`

## Users (admin / super admin)
`GET /users` · `GET /users/{id}` · `PUT\|PATCH /users/{id}` ·
`PATCH /users/{id}/status`
Super Admin: `POST /users` · `DELETE /users/{id}`

## Dashboard & Activity (admin)
`GET /dashboard/statistics` · `GET /admin/activity-logs`

## Settings (Super Admin)
`GET /admin/settings` · `PUT /admin/settings`

---

### Example — register for an event (multipart with file fields)
```bash
curl -X POST /api/v1/events/12/register \
  -H "Authorization: Bearer $TOKEN" \
  -F 'form_response={"t_shirt_size":"L","guests":"2"}' \
  -F 'files[cv]=@resume.pdf'
```

### Example — QR check-in
```bash
curl -X POST /api/v1/admin/attendance/check-in \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"qr":"<qr-token-or-json>","event_id":12}'
```

Per-module request/response detail: see `docs/API.md` (Phase 1),
`EVENTS_MODULE.md`, `PAYMENTS_MODULE.md`, `ANALYTICS_MODULE.md`, `PHASE_5_MODULE.md`.
