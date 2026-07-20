# Security Checklist

## Authentication & Session
- [x] Passwords hashed with bcrypt (`hashed` cast, `BCRYPT_ROUNDS=12`).
- [x] Token auth via Laravel Sanctum; tokens revoked on logout & password reset.
- [x] Password reset tokens expire (60 min) and are single-use.
- [x] Inactive/suspended accounts blocked at login and via `active` middleware
      (token deleted on the spot).
- [ ] Enable email verification (`verified` middleware) — recommended for prod.
- [ ] Add login throttling (`throttle:6,1`) on `/auth/login` & `/auth/*`.
- [ ] Enable 2FA for admin accounts (future).

## Authorization (RBAC)
- [x] Spatie roles/permissions; every admin route gated by `role:` middleware.
- [x] Policies on all models (User, Event, Registration, Payment, Ticket,
      Attendance, Sponsor) — owner-or-admin checks enforced server-side.
- [x] Client-side role checks are UX-only; the API re-authorizes every request.
- [x] Settings management restricted to Super Admin.

## Input & Output
- [x] All write endpoints use FormRequest validation.
- [x] Dynamic event-form answers validated against field definitions server-side.
- [x] Eloquent ORM everywhere (no raw string-concatenated SQL); the few raw
      expressions use bound parameters / whitelisted columns.
- [x] API Resources control the exact response shape (no accidental leakage);
      `qr_signature` and password are hidden.
- [x] File uploads validated by mime + size (images ≤ 2–4 MB, ticket/QR handled
      server-side).

## Payments & Tickets
- [x] Payment verification is server-authoritative (gateway validate/execute);
      client never marks itself paid.
- [x] Sandbox completion guarded by an HMAC-signed token (APP_KEY).
- [x] Idempotent settlement prevents double-processing (IPN + return race).
- [x] QR tokens are random + DB-unique (no duplication) and HMAC-signed;
      check-in verifies the signature before marking attendance.
- [x] Duplicate check-in blocked via row lock.

## Secrets & Config
- [x] Gateway/SMTP/SMS secrets encrypted at rest in `settings` (APP_KEY) and
      masked in the admin API.
- [x] `APP_DEBUG=false` in production; secrets only in `.env` (not committed).
- [ ] Rotate `APP_KEY` procedure documented; use a secrets manager in prod.

## Transport & Headers
- [x] HTTPS enforced (`URL::forceScheme('https')` in prod) + HSTS via Nginx.
- [x] Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy).
- [x] CORS restricted to `FRONTEND_URL`; `supports_credentials` on.
- [x] Secure, same-site session cookies in production.

## Data & Privacy
- [x] Soft deletes on users (recoverable); cascade deletes scoped correctly.
- [x] Activity log records login/registration/payment/attendance/event updates
      with IP + user agent for auditing.
- [x] Directory only exposes active alumni; PII limited in public/list resources.

## Infrastructure
- [x] Nginx denies dotfiles; PHP-FPM hides `X-Powered-By`.
- [x] `client_max_body_size` capped (20M).
- [x] Queue workers run as `www-data`; scheduler `->onOneServer()`.
- [ ] Firewall (ufw): expose only 80/443/22; Postgres/Redis bound to localhost.
- [ ] Fail2ban on SSH; automated security updates.
- [x] Nightly encrypted backups with retention + tested restore.

## Dependencies
- [ ] `composer audit` / `npm audit` in CI.
- [ ] Dependabot / Renovate for patches.
