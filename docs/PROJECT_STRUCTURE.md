# Project Structure

```
AMS/
├── README.md
├── docs/
│   ├── API.md                 # Endpoint reference
│   ├── DATABASE.md            # Schema
│   ├── PROJECT_STRUCTURE.md   # This file
│   └── PHASE_2.md             # Next-phase plan
│
├── backend/                   # ── Laravel 12 API ──────────────────────────
│   ├── app/
│   │   ├── Enums/
│   │   │   ├── RoleType.php
│   │   │   └── UserStatus.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Controller.php
│   │   │   │   └── Api/V1/
│   │   │   │       ├── AuthController.php
│   │   │   │       ├── UserController.php
│   │   │   │       ├── AlumniController.php
│   │   │   │       ├── ProfileController.php
│   │   │   │       └── DashboardController.php
│   │   │   ├── Middleware/
│   │   │   │   └── EnsureUserIsActive.php
│   │   │   ├── Requests/
│   │   │   │   ├── Auth/{Login,Register,ForgotPassword,ResetPassword}Request.php
│   │   │   │   ├── User/{StoreUser,UpdateUser,UpdateUserStatus}Request.php
│   │   │   │   └── Alumni/UpdateProfileRequest.php
│   │   │   └── Resources/
│   │   │       ├── UserResource.php
│   │   │       ├── UserSummaryResource.php
│   │   │       └── AlumniProfileResource.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   └── AlumniProfile.php
│   │   ├── Notifications/
│   │   │   └── ResetPasswordNotification.php
│   │   ├── Policies/
│   │   │   ├── UserPolicy.php
│   │   │   └── AlumniProfilePolicy.php
│   │   ├── Providers/
│   │   │   ├── AppServiceProvider.php
│   │   │   ├── AuthServiceProvider.php
│   │   │   └── RepositoryServiceProvider.php    # binds contracts → impls
│   │   ├── Repositories/
│   │   │   ├── Contracts/                        # interfaces (abstraction)
│   │   │   │   ├── BaseRepositoryInterface.php
│   │   │   │   ├── UserRepositoryInterface.php
│   │   │   │   └── AlumniProfileRepositoryInterface.php
│   │   │   └── Eloquent/                         # implementations
│   │   │       ├── BaseRepository.php
│   │   │       ├── UserRepository.php
│   │   │       └── AlumniProfileRepository.php
│   │   ├── Services/                             # business logic
│   │   │   ├── AuthService.php
│   │   │   ├── UserService.php
│   │   │   ├── AlumniService.php
│   │   │   └── DashboardService.php
│   │   └── Traits/
│   │       └── ApiResponse.php
│   ├── bootstrap/{app.php, providers.php}
│   ├── config/{app,auth,cors,database,permission,sanctum,...}.php
│   ├── database/
│   │   ├── factories/{User,AlumniProfile}Factory.php
│   │   ├── migrations/
│   │   └── seeders/{Database,RolePermission,User}Seeder.php
│   ├── lang/en/passwords.php
│   ├── routes/{api.php, web.php, console.php}
│   ├── tests/Feature/AuthTest.php
│   ├── composer.json
│   └── .env.example
│
└── frontend/                  # ── React 19 SPA ───────────────────────────
    ├── src/
    │   ├── components/
    │   │   ├── ui/            # ShadCN primitives (button, input, card, dialog,
    │   │   │                 #   select, table, avatar, badge, toast, ...)
    │   │   └── common/        # AlumniCard, ProfileForm, Pagination,
    │   │                     #   ProtectedRoute, GuestRoute, StatusBadge
    │   ├── context/AuthContext.tsx
    │   ├── hooks/{useAuth,useDebounce}.ts
    │   ├── layouts/{AuthLayout,DashboardLayout}.tsx
    │   ├── lib/{api.ts, utils.ts}
    │   ├── pages/
    │   │   ├── auth/{Login,Register,ForgotPassword,ResetPassword}Page.tsx
    │   │   ├── dashboard/DashboardPage.tsx
    │   │   ├── users/{UsersPage,UserFormDialog}.tsx
    │   │   ├── alumni/{AlumniDirectoryPage,AlumniManagementPage}.tsx
    │   │   ├── profile/{ProfilePage,EditProfilePage}.tsx
    │   │   └── misc/{IndexRedirect,NotFoundPage,ForbiddenPage}.tsx
    │   ├── services/{auth,user,alumni,dashboard}.service.ts
    │   ├── types/index.ts
    │   ├── App.tsx           # routing
    │   ├── main.tsx
    │   └── index.css
    ├── components.json        # ShadCN config
    ├── tailwind.config.js
    ├── vite.config.ts
    ├── package.json
    └── .env.example
```

## Clean Architecture — request flow

```
HTTP Request
   │
   ▼
Route (routes/api.php)  ──▶  Middleware (auth:sanctum, active, role:*)
   │
   ▼
Controller (Api/V1/*)   ──▶  FormRequest (validation)  +  Policy (authorization)
   │
   ▼
Service (business logic / transactions)
   │
   ▼
Repository Contract  ──(bound)──▶  Eloquent Repository
   │
   ▼
Model / Database
   │
   ▼
API Resource (response shaping)  ──▶  ApiResponse trait (envelope)  ──▶  JSON
```

**Why this layering?**
- Controllers stay thin — only HTTP concerns.
- Services own business rules and are framework-agnostic and unit-testable.
- Repositories hide persistence behind interfaces (swap Eloquent later without
  touching services).
- Policies centralize authorization; FormRequests centralize validation.
