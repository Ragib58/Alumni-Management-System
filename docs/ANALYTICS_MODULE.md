# Phase 4 — Attendance, Analytics & Reports

Extends Phase 3 with a QR check-in attendance system, an analytics dashboard,
reports with Excel/CSV/PDF export, and year-over-year comparison. **Additive
only** — earlier files were extended (providers, routes, seeders, `App.tsx`,
`DashboardLayout`, `AdminEventListPage`, `types`), never rewritten.

## New dependency (backend)

```
maatwebsite/excel   # Excel (.xlsx) + CSV export (PDF uses the existing dompdf)
```

## Database Schema

### `attendances`
| Column          | Type          | Notes                                       |
| --------------- | ------------- | ------------------------------------------- |
| id              | bigint PK     |                                             |
| registration_id | FK **unique** | one attendance record per registration      |
| event_id        | FK            | denormalized for fast per-event analytics   |
| status          | varchar       | not_arrived · checked_in · checked_out      |
| checkin_time    | timestamp     |                                             |
| checkout_time   | timestamp     |                                             |
| checked_by      | FK→users      | the admin/scanner who performed the check-in|

## QR Check-In Workflow

```
1. Admin opens scanner          /admin/attendance/scan (native BarcodeDetector)
2. Scan QR                       decode { t: token, r: registration_no }
3. Verify registration          CheckInService: token exists → signature valid
                                 (HMAC) → event matches → not cancelled
4. Mark attendance               status=checked_in, checkin_time, checked_by
```

- **Duplicate prevention**: the attendance row is locked (`lockForUpdate`); if
  it's already `checked_in`/`checked_out`, the API returns `duplicate: true`
  instead of re-checking-in.
- **Check-out**: `checked_in → checked_out` with `checkout_time` (idempotent).
- The frontend scanner uses the browser-native `BarcodeDetector` API with a
  **manual-entry fallback** (paste QR value / token) — no JS QR dependency.

## Analytics (cached)

`AnalyticsService` caches every aggregate (`Cache::remember`, TTL from
`config/analytics.php`, default 300s) and exposes `flush()` — called after every
check-in / payment mutation so numbers stay fresh.

**Cards:** Total Events · Total Registrations · Total Attendance · Total Revenue
**Charts:** Monthly Revenue · Event Participation · Attendance Trend · Registration Trend

Queries are single grouped aggregates (no N+1); month/year bucketing is
driver-portable via `App\Support\SqlDate` (pgsql / mysql / sqlite).

## Year Comparison

`GET /admin/analytics/year-comparison?year_a=2025&year_b=2026` returns revenue,
participation and attendance for each year with **growth %**, plus chart-ready
`series` (bar) and 12-month `monthly` revenue (line).

## Reports & Export

`ReportService` builds three datasets; `ExportService` renders them to file:

| Report     | Contents                                            |
| ---------- | --------------------------------------------------- |
| Event      | Registrations · Attendance · Attendance % · Revenue |
| Financial  | Revenue · Refunds · Transactions (per-txn table)    |
| Alumni     | Batch-wise + Department-wise participation          |

Export formats: **Excel** (`.xlsx`) and **CSV** via `ReportExport`
(maatwebsite/excel), **PDF** via a dompdf Blade view (`reports.table`).

## API Endpoints (admin: Super Admin / Event Manager)

### Attendance
| Method | Endpoint                                       | Description                 |
| ------ | ---------------------------------------------- | --------------------------- |
| POST   | `/admin/attendance/check-in`                   | Scan QR / manual check-in   |
| POST   | `/admin/attendance/check-out`                  | Check a participant out     |
| GET    | `/admin/events/{event}/attendance`             | Attendance list (+ stats)   |
| GET    | `/admin/events/{event}/attendance/stats`       | Attendance stats            |

Check-in body: `{ qr }` **or** `{ registration_id }`, optional `{ event_id }`.

### Analytics
| Method | Endpoint                                  | Description               |
| ------ | ----------------------------------------- | ------------------------- |
| GET    | `/admin/analytics/dashboard`              | Cards + all charts        |
| GET    | `/admin/analytics/year-comparison`        | Year A vs Year B          |

### Reports
| Method | Endpoint                                       | Description            |
| ------ | ---------------------------------------------- | --------------------- |
| GET    | `/admin/reports/event`                         | Event report          |
| GET    | `/admin/reports/financial`                     | Financial report      |
| GET    | `/admin/reports/alumni`                        | Alumni participation  |
| GET    | `/admin/reports/{type}/export/{format}`        | Download (excel/csv/pdf) |

## Permissions added (`AttendancePermissionSeeder`)
`attendance.scan · attendance.view · analytics.view · reports.view · reports.export`
— granted to Super Admin & Event Manager.

## Reusable Chart Components (`frontend/src/components/charts/`)

| Component        | Props (data-driven)                                    |
| ---------------- | ------------------------------------------------------ |
| `StatCard`       | title, value, icon, accent, optional `delta%`          |
| `ChartCard`      | shell: title, height, empty state                      |
| `BarChartCard`   | data, xKey, `series[]`, valueFormatter, stacked        |
| `LineChartCard`  | data, xKey, `series[]`, valueFormatter                 |
| `AreaChartCard`  | data, xKey, `series[]` (gradient fill)                 |
| `PieChartCard`   | data, nameKey, valueKey, valueFormatter                |

Shared palette + tooltip/axis styling live in `chart-theme.ts`, so every chart
across the app is visually consistent (and theme-aware).

## Frontend Pages (admin)

- `/admin/attendance/scan` — QR scanner + manual entry + live scan feed
- `/admin/events/:id/attendance` — attendance list, stats, manual check-in/out
- `/admin/analytics` — cards + 4 charts
- `/admin/reports` — Event / Financial / Alumni tabs + Excel/CSV/PDF export
- `/admin/year-comparison` — 2025 vs 2026 growth cards + bar & line charts

## Run

```bash
cd backend
composer install                 # pulls maatwebsite/excel
php artisan migrate --seed       # attendances table + demo attendance
cd ../frontend && npm run dev
```

Demo: sign in as `manager@ams.test` → **Analytics** for the dashboard,
**QR Check-In** to scan tickets (or paste a ticket's QR token), **Reports** to
export, **Year comparison** for growth. The seeder marks ~60% of confirmed
registrations as attended so charts and reports have data.
