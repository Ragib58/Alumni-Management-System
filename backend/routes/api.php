<?php

use App\Http\Controllers\Api\V1\Admin\ActivityLogController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AttendanceController;
use App\Http\Controllers\Api\V1\Admin\PaymentAdminController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\SponsorController;
use App\Http\Controllers\Api\V1\AlumniController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentCallbackController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicEventController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Roles: super_admin | event_manager | alumni_member | guest
| All protected routes use the Sanctum guard + `active` account gate.
|
*/

Route::prefix('v1')->group(function () {

    /* ------------------------- Public auth endpoints ------------------------ */
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    /* ------------------- Public events (no auth required) ------------------- */
    Route::prefix('public')->group(function () {
        Route::get('events', [PublicEventController::class, 'index']);
        Route::get('settings', [SettingsController::class, 'publicSettings']);
        Route::get('events/{slug}', [PublicEventController::class, 'show']);

        // Gateway callbacks (live mode) — unauthenticated, verified by signature.
        Route::match(['get', 'post'], 'payments/{gateway}/return', [PaymentCallbackController::class, 'return'])
            ->name('payments.return');
        Route::post('payments/{gateway}/ipn', [PaymentCallbackController::class, 'ipn'])
            ->name('payments.ipn');
    });

    /* --------------------------- Protected surface -------------------------- */
    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        // Session
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });

        // My own alumni profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('profile', [ProfileController::class, 'update']); // multipart fallback

        /* ------------------- In-app notifications (all users) -------------- */
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        // Alumni Directory — any authenticated user can browse.
        Route::get('alumni', [AlumniController::class, 'index']);
        Route::get('alumni/filters', [AlumniController::class, 'filters']);
        Route::get('alumni/{alumni}', [AlumniController::class, 'show'])->whereNumber('alumni');
        Route::put('alumni/{alumni}', [AlumniController::class, 'update'])->whereNumber('alumni');
        Route::post('alumni/{alumni}', [AlumniController::class, 'update'])->whereNumber('alumni'); // multipart fallback

        /* ----------------------------- Events ------------------------------ */
        // Browse (any authenticated user; non-admins see published only).
        Route::get('events', [EventController::class, 'index']);
        Route::get('events/meta', [EventController::class, 'meta']);
        Route::get('events/slug/{slug}', [EventController::class, 'showBySlug']);

        // Registration (self-service)
        Route::get('my-registrations', [RegistrationController::class, 'myRegistrations']);
        Route::get('my-registrations/{registration}', [RegistrationController::class, 'showOwn'])->whereNumber('registration');
        Route::post('events/{event}/register', [RegistrationController::class, 'register'])->whereNumber('event');
        Route::delete('registrations/{registration}/cancel', [RegistrationController::class, 'cancel'])->whereNumber('registration');

        /* ---------------------------- Payments ----------------------------- */
        Route::post('registrations/{registration}/pay', [PaymentController::class, 'initiate'])->whereNumber('registration');
        Route::post('payments/{payment}/sandbox-complete', [PaymentController::class, 'sandboxComplete'])->whereNumber('payment');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->whereNumber('payment');
        Route::get('my-payments', [PaymentController::class, 'myPayments']);

        /* ----------------------------- Tickets ----------------------------- */
        Route::get('my-tickets', [TicketController::class, 'index']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket');
        Route::get('tickets/{ticket}/download', [TicketController::class, 'download'])->whereNumber('ticket');
        Route::post('tickets/{ticket}/email', [TicketController::class, 'email'])->whereNumber('ticket');

        /* ------------------- Admin: Super Admin / Event Manager ------------- */
        Route::middleware('role:super_admin|event_manager')->group(function () {

            // Dashboard statistics
            Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);

            // Event CRUD
            Route::get('events/{event}', [EventController::class, 'show'])->whereNumber('event');
            Route::post('events', [EventController::class, 'store']);
            Route::put('events/{event}', [EventController::class, 'update'])->whereNumber('event');
            Route::post('events/{event}', [EventController::class, 'update'])->whereNumber('event'); // multipart fallback
            Route::delete('events/{event}', [EventController::class, 'destroy'])->whereNumber('event');

            // Registration management
            Route::get('registrations', [RegistrationController::class, 'index']);
            Route::get('events/{event}/registrations', [RegistrationController::class, 'index'])->whereNumber('event');
            Route::get('registrations/{registration}', [RegistrationController::class, 'show'])->whereNumber('registration');
            Route::patch('registrations/{registration}/status', [RegistrationController::class, 'updateStatus'])->whereNumber('registration');

            // Payment management + Revenue dashboard
            Route::get('admin/payments', [PaymentAdminController::class, 'index']);
            Route::get('admin/payments-revenue', [PaymentAdminController::class, 'revenue']);
            Route::get('admin/payments/{payment}', [PaymentAdminController::class, 'show'])->whereNumber('payment');
            Route::post('admin/payments/{payment}/refund', [PaymentAdminController::class, 'refund'])->whereNumber('payment');

            /* ----------------- Attendance / QR check-in (Phase 4) ---------- */
            Route::post('admin/attendance/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('admin/attendance/check-out', [AttendanceController::class, 'checkOut']);
            Route::get('admin/events/{event}/attendance', [AttendanceController::class, 'index'])->whereNumber('event');
            Route::get('admin/events/{event}/attendance/stats', [AttendanceController::class, 'stats'])->whereNumber('event');

            /* --------------------- Analytics & Reports (Phase 4) ----------- */
            Route::get('admin/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
            Route::get('admin/analytics/year-comparison', [AnalyticsController::class, 'yearComparison']);

            Route::get('admin/reports/event', [ReportController::class, 'event']);
            Route::get('admin/reports/financial', [ReportController::class, 'financial']);
            Route::get('admin/reports/alumni', [ReportController::class, 'alumni']);
            Route::get('admin/reports/{type}/export/{format}', [ReportController::class, 'export']);

            /* ------------------------- Sponsors (Phase 5) ------------------ */
            Route::get('admin/sponsors', [SponsorController::class, 'index']);
            Route::get('admin/sponsors/meta', [SponsorController::class, 'meta']);
            Route::get('admin/sponsors/{sponsor}', [SponsorController::class, 'show'])->whereNumber('sponsor');
            Route::post('admin/sponsors', [SponsorController::class, 'store']);
            Route::put('admin/sponsors/{sponsor}', [SponsorController::class, 'update'])->whereNumber('sponsor');
            Route::post('admin/sponsors/{sponsor}', [SponsorController::class, 'update'])->whereNumber('sponsor'); // multipart fallback
            Route::delete('admin/sponsors/{sponsor}', [SponsorController::class, 'destroy'])->whereNumber('sponsor');

            /* ----------------------- Activity logs (Phase 5) --------------- */
            Route::get('admin/activity-logs', [ActivityLogController::class, 'index']);

            // User Management
            Route::get('users', [UserController::class, 'index']);
            Route::get('users/{user}', [UserController::class, 'show'])->whereNumber('user');
            Route::put('users/{user}', [UserController::class, 'update'])->whereNumber('user');
            Route::patch('users/{user}', [UserController::class, 'update'])->whereNumber('user');
            Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->whereNumber('user');
        });

        /* ------------------------ Admin: Super Admin only ------------------- */
        Route::middleware('role:super_admin')->group(function () {
            Route::post('users', [UserController::class, 'store']);
            Route::delete('users/{user}', [UserController::class, 'destroy'])->whereNumber('user');

            // Site / payment / email / sms / theme settings (Phase 5)
            Route::get('admin/settings', [SettingsController::class, 'index']);
            Route::put('admin/settings', [SettingsController::class, 'update']);
        });
    });
});

// Simple health probe for load balancers / uptime checks.
Route::get('/health', fn () => response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]));
