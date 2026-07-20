# Phase 2 — Event Management Module

Extends Phase 1 (auth, RBAC, users, alumni) with events, a dynamic registration
form builder, and a registration workflow. All code is **additive** — no Phase 1
files were rewritten (only extended: `routes/api.php`, the two providers,
`DatabaseSeeder`, frontend `App.tsx`, `DashboardLayout`, `types`, `utils`).

## Database Schema

### `events`
| Column             | Type            | Notes                                              |
| ------------------ | --------------- | -------------------------------------------------- |
| id                 | bigint PK       |                                                    |
| title              | varchar         |                                                    |
| slug               | varchar         | **unique**, auto-generated from title              |
| banner             | varchar (null)  | storage path (`events/…`)                          |
| description        | text (null)     |                                                    |
| venue              | varchar (null)  |                                                    |
| type               | varchar         | reunion·seminar·workshop·sports·cultural_program·iftar |
| event_date         | timestamp       | indexed                                            |
| registration_start | timestamp (null)|                                                    |
| registration_end   | timestamp (null)|                                                    |
| fee                | decimal(10,2)   | 0 = free                                           |
| max_capacity       | int (null)      | null = unlimited                                   |
| status             | varchar         | draft·published·closed·completed                   |
| created_by         | bigint FK→users | nullOnDelete                                        |
| timestamps, deleted_at (soft delete)                                                    |

### `event_form_fields` (dynamic form builder)
| Column      | Type          | Notes                                                     |
| ----------- | ------------- | --------------------------------------------------------- |
| id          | bigint PK     |                                                           |
| event_id    | bigint FK     | cascade delete                                            |
| label       | varchar       | display label                                             |
| name        | varchar       | machine key, **unique per event**                         |
| type        | varchar       | text·number·email·select·checkbox·radio·textarea·file     |
| options     | json (null)   | for select/checkbox/radio                                 |
| is_required | boolean       |                                                           |
| placeholder | varchar (null)|                                                           |
| help_text   | varchar (null)|                                                           |
| sort_order  | int           |                                                           |

### `event_registrations`
| Column          | Type          | Notes                                        |
| --------------- | ------------- | -------------------------------------------- |
| id              | bigint PK     |                                              |
| registration_no | varchar       | **unique**, `REG-YYYY-NNNN`                  |
| event_id        | bigint FK     | cascade delete                               |
| user_id         | bigint FK     | cascade delete                               |
| status          | varchar       | pending·confirmed·cancelled                  |
| payment_status  | varchar       | pending·paid·failed·refunded·free            |
| amount          | decimal(10,2) | snapshot of event fee                        |
| form_response   | json (null)   | `{ field_name: value }`                      |
| registered_at   | timestamp     |                                              |
| cancelled_at    | timestamp     |                                              |
| unique(event_id, user_id) — one registration per user per event               |

## Registration Flow

```
Step 1  User opens event page          →  GET /events/slug/{slug}
Step 2  User fills registration form    →  DynamicFormRenderer (event.form_fields)
Step 3  Registration created            →  POST /events/{event}/register  (status: pending)
Step 4  Payment pending status assigned →  payment_status = pending (paid events) | free
```

**Capacity control** (enforced in `RegistrationService`):
- event must be `published`
- `now` within `[registration_start, registration_end]`
- active (pending+confirmed) count `< max_capacity`
- one registration per user per event
- capacity re-checked inside a `lockForUpdate` transaction to prevent overbooking races.

## API Endpoints

Base: `/api/v1`. Auth via Bearer token unless marked **public**.

### Public (no auth)
| Method | Endpoint                     | Description                     |
| ------ | ---------------------------- | ------------------------------- |
| GET    | `/public/events`             | Published events (search, type) |
| GET    | `/public/events/{slug}`      | Single published event          |

### Events — authenticated
| Method | Endpoint                     | Access        | Description                        |
| ------ | ---------------------------- | ------------- | ---------------------------------- |
| GET    | `/events`                    | any           | Catalogue (non-admins see published)|
| GET    | `/events/meta`               | any           | Enum options (types/status/field)  |
| GET    | `/events/slug/{slug}`        | any           | Details + form fields              |
| GET    | `/events/{id}`               | admin         | Single event by id                 |
| POST   | `/events`                    | admin         | Create (multipart: banner + form_fields JSON) |
| PUT/POST | `/events/{id}`             | admin         | Update                             |
| DELETE | `/events/{id}`               | admin         | Delete                             |

### Registrations
| Method | Endpoint                                   | Access | Description                   |
| ------ | ------------------------------------------ | ------ | ----------------------------- |
| POST   | `/events/{event}/register`                 | user   | Register (form_response + files)|
| GET    | `/my-registrations`                        | user   | Current user's registrations  |
| DELETE | `/registrations/{id}/cancel`               | user   | Cancel own                    |
| GET    | `/registrations`                           | admin  | All registrations (filter)    |
| GET    | `/events/{event}/registrations`            | admin  | Registrations for one event   |
| GET    | `/registrations/{id}`                      | admin  | Single registration           |
| PATCH  | `/registrations/{id}/status`               | admin  | Update status / payment_status|

### Register payload (multipart)
```
form_response = JSON string { "t_shirt_size": "L", "guests": "2", ... }
files[<field_name>] = <binary>       # for file-type fields
```

## Permissions added (`EventPermissionSeeder`)
`events.view · events.create · events.update · events.delete · events.publish`
`registrations.view · registrations.manage · registrations.create · registrations.cancel`

| Permission            | Super Admin | Event Manager | Alumni | Guest |
| --------------------- | :---------: | :-----------: | :----: | :---: |
| events.view           | ✅          | ✅            | ✅     | ✅    |
| events.create/update/delete/publish | ✅ | ✅   |        |       |
| registrations.view/manage | ✅      | ✅            |        |       |
| registrations.create/cancel | ✅    |               | ✅     |       |

## Frontend Pages

**Admin** (`/admin/*`, Super Admin / Event Manager):
- `/admin/events` — Event list (search, status filter, delete)
- `/admin/events/create` · `/admin/events/:id/edit` — Event form with **FormBuilder**
- `/admin/events/:id/registrations` · `/admin/registrations` — Registration list + status control

**User** (authenticated):
- `/events` — Event list (cards, search, type filter)
- `/events/:slug` — Event details
- `/events/:slug/register` — Registration form (dynamic fields → confirmation)
- `/my-registrations` — My registrations (+ cancel)

**Public** (no login):
- `/public/events` · `/public/events/:slug`

## Key Files

```
backend/app/
  Enums/{EventStatus,EventType,RegistrationStatus,PaymentStatus,FormFieldType}.php
  Models/{Event,EventFormField,EventRegistration}.php
  Repositories/Contracts/{EventRepositoryInterface,EventRegistrationRepositoryInterface}.php
  Repositories/Eloquent/{EventRepository,EventRegistrationRepository}.php
  Services/{EventService,RegistrationService}.php
  Http/Requests/Event/{StoreEvent,UpdateEvent,RegisterEvent,UpdateRegistrationStatus}Request.php
  Http/Resources/{EventResource,EventFormFieldResource,EventRegistrationResource}.php
  Policies/{EventPolicy,EventRegistrationPolicy}.php
  Http/Controllers/Api/V1/{EventController,PublicEventController,RegistrationController}.php
backend/database/
  migrations/2024_02_01_0000{01,02,03}_*.php
  factories/{Event,EventFormField,EventRegistration}Factory.php
  seeders/{EventPermissionSeeder,EventSeeder}.php

frontend/src/
  types/index.ts (Phase 2 section)
  services/{event,registration}.service.ts
  components/common/{EventCard,EventStatusBadge,EventDetailsView,DynamicFormRenderer,FormBuilder}.tsx
  pages/events/{EventListPage,EventDetailsPage,RegistrationFormPage,MyRegistrationsPage}.tsx
  pages/events/admin/{AdminEventListPage,EventFormPage,RegistrationListPage}.tsx
  pages/events/public/{PublicEventsPage,PublicEventDetailsPage}.tsx
```

## Run

```bash
# Backend (after PHP 8.2+ is available)
cd backend
php artisan migrate --seed        # adds event tables + demo events
php artisan storage:link          # serve event banners / uploads

# Frontend
cd frontend
npm run dev
```

Demo: sign in as `manager@ams.test` (Password123!) → **Event Management** to create
events; sign in as `alumni@ams.test` → **Events** to register. The seeded
"Grand Alumni Reunion 2024" already has a dynamic form and sample registrations.
