# Complete Folder Structure

```
AMS/
├── README.md
├── docs/
│   ├── API.md · API_REFERENCE.md            # endpoint reference
│   ├── DATABASE.md · DATABASE_SCHEMA.md      # schema (full)
│   ├── ER_DIAGRAM.md                         # Mermaid ER
│   ├── PROJECT_STRUCTURE.md · FOLDER_STRUCTURE.md
│   ├── EVENTS_MODULE.md                      # Phase 2
│   ├── PAYMENTS_MODULE.md                    # Phase 3
│   ├── ANALYTICS_MODULE.md                   # Phase 4
│   ├── PHASE_5_MODULE.md                     # Phase 5
│   ├── DEPLOYMENT.md
│   └── SECURITY_CHECKLIST.md
│
├── deploy/                                   # ── Production ops ──
│   ├── nginx.conf
│   ├── supervisor/ams-worker.conf            # queue workers
│   ├── crontab.txt                           # scheduler + backup cron
│   └── backup.sh                             # pg_dump + files, retention
│
├── backend/                                  # ── Laravel 12 API ──
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   ├── SendEventReminders.php
│   │   │   └── SendThankYouMessages.php
│   │   ├── Enums/
│   │   │   ├── RoleType · UserStatus
│   │   │   ├── EventStatus · EventType · RegistrationStatus · PaymentStatus · FormFieldType
│   │   │   ├── PaymentGateway · AttendanceStatus
│   │   │   └── SponsorType · ActivityAction
│   │   ├── Exports/ReportExport.php          # Excel/CSV
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/
│   │   │   │   ├── Auth · User · Alumni · Profile · Dashboard
│   │   │   │   ├── Event · PublicEvent · Registration
│   │   │   │   ├── Payment · PaymentCallback · Ticket
│   │   │   │   ├── Notification · Settings
│   │   │   │   └── Admin/{PaymentAdmin, Attendance, Analytics, Report, Sponsor, ActivityLog}
│   │   │   ├── Middleware/EnsureUserIsActive.php
│   │   │   ├── Requests/{Auth,User,Alumni,Event,Payment,Attendance,Sponsor,Setting}/
│   │   │   └── Resources/                     # API transformers (14)
│   │   ├── Jobs/{GenerateTicketJob, SendTicketEmailJob}.php
│   │   ├── Mail/TicketMail.php
│   │   ├── Models/
│   │   │   ├── User · AlumniProfile
│   │   │   ├── Event · EventFormField · EventRegistration
│   │   │   ├── Payment · Ticket · Attendance
│   │   │   └── Sponsor · Setting · ActivityLog
│   │   ├── Notifications/
│   │   │   ├── BaseNotification.php
│   │   │   ├── RegistrationConfirmed · PaymentSuccess · EventReminder · EventUpdated · ThankYou
│   │   │   ├── ResetPasswordNotification.php
│   │   │   ├── Channels/SmsChannel.php
│   │   │   └── Messages/SmsMessage.php
│   │   ├── Policies/                          # 8 policies
│   │   ├── Providers/{App, Auth, Repository}ServiceProvider.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/                     # interfaces
│   │   │   └── Eloquent/                      # implementations (8 repos)
│   │   ├── Services/
│   │   │   ├── AuthService · UserService · AlumniService · DashboardService
│   │   │   ├── EventService · RegistrationService
│   │   │   ├── Payment/ (gateway layer: Contracts, Data, Gateways, Manager)
│   │   │   ├── PaymentService · QrService · TicketService
│   │   │   ├── CheckInService · AnalyticsService · ReportService · ExportService
│   │   │   ├── SmsService · SettingsService · ActivityLogger
│   │   │   ├── SponsorService · NotificationDispatcher
│   │   │   └── (Support/SqlDate.php)
│   │   ├── Support/SqlDate.php
│   │   └── Traits/ApiResponse.php
│   │   └── Enums, Exports … (as above)
│   ├── bootstrap/{app.php, providers.php}
│   ├── config/                               # app, auth, cors, database, sanctum,
│   │                                         # permission, payment, sms, analytics, …
│   ├── database/
│   │   ├── factories/                        # User, AlumniProfile, Event, …, Sponsor
│   │   ├── migrations/                        # 0001 → 2024_05_01 (all phases)
│   │   └── seeders/                           # Role/User + per-phase permission & data
│   ├── resources/views/
│   │   ├── tickets/ticket.blade.php           # PDF ticket (QR)
│   │   ├── emails/ticket.blade.php
│   │   └── reports/table.blade.php            # PDF report export
│   ├── routes/{api.php, web.php, console.php} # console.php holds the scheduler
│   ├── tests/
│   │   ├── Unit/{QrServiceTest, SettingsServiceTest}
│   │   └── Feature/{Auth, AttendanceCheckIn, Sponsor, Notification, SettingsApi}Test
│   ├── composer.json
│   ├── .env.example · .env.production.example
│   └── artisan
│
└── frontend/                                 # ── React 19 + TS + Vite ──
    ├── src/
    │   ├── components/
    │   │   ├── ui/                            # ShadCN primitives
    │   │   ├── charts/                        # StatCard, ChartCard, Bar/Line/Area/Pie (reusable)
    │   │   └── common/                        # guards, cards, forms, QrScanner,
    │   │                                      # NotificationBell, SponsorList, DynamicForm, FormBuilder
    │   ├── context/{AuthContext, SettingsContext}.tsx
    │   ├── hooks/{useAuth, useDebounce}.ts
    │   ├── layouts/{AuthLayout, DashboardLayout}.tsx
    │   ├── lib/{api.ts, utils.ts}
    │   ├── pages/
    │   │   ├── auth/ · dashboard/ · users/ · alumni/ · profile/ · misc/
    │   │   ├── events/ (+ admin/, public/)
    │   │   ├── payments/ (+ admin/) · tickets/
    │   │   ├── attendance/ · analytics/
    │   │   ├── sponsors/ · settings/ · activity/ · notifications/   ← Phase 5 (lazy-loaded)
    │   ├── services/                          # one module per domain (13 services)
    │   ├── types/                             # index.ts + barcode-detector.d.ts
    │   ├── App.tsx  · main.tsx · index.css
    │   └── vite-env.d.ts
    ├── components.json · tailwind.config.js · vite.config.ts
    ├── tsconfig*.json · eslint.config.js · postcss.config.js
    ├── package.json
    └── .env.example
```

## Clean-architecture request flow (unchanged across phases)

```
Route → Middleware (auth:sanctum, active, role) → Controller
  → FormRequest (validation) + Policy (authorization)
  → Service (business logic, transactions, notifications, activity log)
  → Repository Contract → Eloquent Repository → Model/DB
  → API Resource → ApiResponse envelope → JSON
```
