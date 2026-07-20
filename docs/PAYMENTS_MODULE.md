# Phase 3 — Payments, Tickets & QR

Extends Phase 2 (events + registrations) with payment gateways, a ticket module
(PDF + QR + email), and a revenue dashboard. **Additive only** — Phase 1/2 files
were extended (providers, routes, seeders, `RegistrationService`,
`MyRegistrationsPage`, `RegistrationFormPage`, `App.tsx`, `DashboardLayout`,
`types`), never rewritten.

## New dependencies (backend)

```
barryvdh/laravel-dompdf        # PDF ticket rendering
simplesoftwareio/simple-qrcode # QR (SVG) generation
```

## Database Schema

### `payments`
| Column                 | Type          | Notes                                       |
| ---------------------- | ------------- | ------------------------------------------- |
| id                     | bigint PK     |                                             |
| registration_id        | FK→event_registrations | cascade delete                     |
| transaction_id         | varchar       | **unique** — our ref sent to the gateway    |
| gateway_transaction_id | varchar (null)| gateway's own ref (val_id / trxID / …)      |
| amount                 | decimal(10,2) |                                             |
| currency               | varchar(8)    | default BDT                                 |
| gateway                | varchar       | sslcommerz · bkash · nagad                  |
| status                 | varchar       | pending · paid · failed · refunded          |
| payment_date           | timestamp     | set when paid                               |
| meta                   | json          | gateway request/response audit trail        |

### `tickets`
| Column          | Type          | Notes                                        |
| --------------- | ------------- | -------------------------------------------- |
| id              | bigint PK     |                                              |
| registration_id | FK **unique** | one ticket per registration                  |
| ticket_no       | varchar unique| `TKT-YYYY-XXXXXX`                            |
| qr_token        | varchar(64) **unique** | opaque value encoded in the QR      |
| qr_signature    | varchar       | HMAC(token,reg) keyed by APP_KEY (hidden)    |
| pdf_path        | varchar (null)| stored PDF on public disk                    |
| issued_at / emailed_at / checked_in_at | timestamp | lifecycle          |

## Payment Flow

```
1. Registration submitted        (Phase 2 → payment_status=pending)
2. Redirect payment              POST /registrations/{id}/pay {gateway}
                                 → creates Payment(pending) → returns redirect_url
3. Verify transaction            gateway return/IPN  → PaymentService::applyResult
                                 (sandbox: POST /payments/{id}/sandbox-complete)
4. Update registration status    paid → registration.status=confirmed, payment_status=paid
5. Generate ticket               GenerateTicketJob → QR + PDF → SendTicketEmailJob
```

Free events (fee = 0) skip payment: the registration is confirmed immediately
and `GenerateTicketJob` is dispatched at registration time.

### Sandbox vs Live (`PAYMENT_MODE`)

- **sandbox** (default): `initiate()` returns a redirect to the SPA's simulated
  gateway page (`/payment/simulate`). The page reports the outcome to
  `POST /payments/{id}/sandbox-complete`, guarded by a signed HMAC token — the
  whole flow works with **no gateway credentials**.
- **live**: gateways perform real API calls (SSLCommerz session/validate, bKash
  tokenized create/execute, Nagad initialize/verify) and redirect to
  `/api/v1/public/payments/{gateway}/return` (+ `/ipn`), which verifies and
  bounces the browser to the SPA success/failed page.

## Gateway Service Layer

```
App\Services\Payment\
  Contracts\PaymentGatewayInterface        initiate() + verify()
  Data\InitiateResult, VerificationResult  value objects
  Gateways\AbstractGateway                 sandbox simulator + HMAC token
  Gateways\SslcommerzGateway               live: create session → validate
  Gateways\BkashGateway                    live: grant token → create → execute
  Gateways\NagadGateway                    live: RSA-signed initialize → verify
  PaymentGatewayManager                    factory: make('bkash') → adapter
```

Adding a gateway = implement `PaymentGatewayInterface` + register in the manager.

## QR Module (`QrService`)

- `generateToken()` — random 40-char token, uniqueness enforced by the DB index
  (**duplication impossible**).
- `sign()` / `verifySignature()` — HMAC(token|reg_id|reg_no, APP_KEY); the QR is
  tamper-evident and verifiable at check-in without trusting the client.
- `payload()` — compact JSON `{ t: token, r: registration_no }` encoded in the QR.
- `svg()` / `svgDataUri()` — renders an SVG QR (no image extension needed).

## Ticket Module (`TicketService`)

- `generateFor()` — idempotent: creates the ticket + QR, renders the PDF (Blade
  `tickets.ticket` via dompdf), stores it on the public disk.
- `ensurePdfPath()` — lazily (re)renders for downloads.
- `email()` — sends `TicketMail` with the PDF attached; queued via jobs.

## Queue Jobs

- `GenerateTicketJob(registrationId)` — QR + PDF, then dispatches the email job.
- `SendTicketEmailJob(ticketId)` — emails the ticket (retryable, isolated).

Run a worker: `php artisan queue:work` (QUEUE_CONNECTION=database).

## API Endpoints

Base `/api/v1`. Bearer auth unless marked **public**.

### Payments
| Method | Endpoint                                   | Access | Description                    |
| ------ | ------------------------------------------ | ------ | ------------------------------ |
| POST   | `/registrations/{id}/pay`                  | user   | Initiate payment (returns redirect_url) |
| POST   | `/payments/{id}/sandbox-complete`          | user   | Settle sandbox payment         |
| GET    | `/payments/{id}`                           | owner  | Poll payment status            |
| GET    | `/my-payments`                             | user   | Payment history                |
| GET    | `/public/payments/{gateway}/return`        | public | Live gateway browser return    |
| POST   | `/public/payments/{gateway}/ipn`           | public | Live gateway IPN               |

### Tickets
| Method | Endpoint                       | Access | Description               |
| ------ | ------------------------------ | ------ | ------------------------- |
| GET    | `/my-tickets`                  | user   | List own tickets          |
| GET    | `/tickets/{id}`                | owner  | Ticket details            |
| GET    | `/tickets/{id}/download`       | owner  | Stream the PDF            |
| POST   | `/tickets/{id}/email`          | owner  | (Re)send by email         |

### Admin (Super Admin / Event Manager)
| Method | Endpoint                              | Description               |
| ------ | ------------------------------------- | ------------------------- |
| GET    | `/admin/payments`                     | Payment list (filters)    |
| GET    | `/admin/payments/{id}`                | Transaction details       |
| POST   | `/admin/payments/{id}/refund`         | Refund a paid payment     |
| GET    | `/admin/payments-revenue`             | Revenue dashboard data    |

## Permissions added (`PaymentPermissionSeeder`)
`payments.pay · payments.view · payments.refund · revenue.view · tickets.view`

| Permission     | Super Admin | Event Manager | Alumni |
| -------------- | :---------: | :-----------: | :----: |
| payments.pay   | ✅          |               | ✅     |
| tickets.view   | ✅          |               | ✅     |
| payments.view  | ✅          | ✅            |        |
| payments.refund| ✅          | ✅            |        |
| revenue.view   | ✅          | ✅            |        |

## Frontend Pages

**User:** `/registrations/:id/pay` (Payment), `/payment/simulate` (sandbox
gateway), `/payment/success`, `/payment/failed`, `/my-tickets` (download + email).

**Admin:** `/admin/payments` (list), `/admin/payments/:id` (transaction + refund),
`/admin/revenue` (revenue dashboard with monthly/gateway/event charts).

## Configuration

`config/payment.php` + `.env` (see `.env.example`):
```
PAYMENT_MODE=sandbox            # sandbox | live
PAYMENT_CURRENCY=BDT
SSLCZ_STORE_ID= …               # per-gateway credentials for live mode
BKASH_APP_KEY= …
NAGAD_MERCHANT_ID= …
```

## Run

```bash
cd backend
composer install                # pulls dompdf + simple-qrcode
php artisan migrate --seed      # payments + tickets tables, demo data
php artisan storage:link        # serve ticket PDFs
php artisan queue:work          # process ticket generation + emails

cd ../frontend && npm run dev
```

Demo (sandbox): sign in as `alumni@ams.test` → **Events** → register for the paid
"Grand Alumni Reunion 2024" → **Pay now** → choose a gateway → **Success** on the
simulator → ticket appears under **My Tickets**. Admins see it under
**Payments** / **Revenue**.
