<?php

use App\Http\Controllers\Api\V1\AlumniController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProfileController;
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

        // Alumni Directory — any authenticated user can browse.
        Route::get('alumni', [AlumniController::class, 'index']);
        Route::get('alumni/filters', [AlumniController::class, 'filters']);
        Route::get('alumni/{alumni}', [AlumniController::class, 'show'])->whereNumber('alumni');
        Route::put('alumni/{alumni}', [AlumniController::class, 'update'])->whereNumber('alumni');
        Route::post('alumni/{alumni}', [AlumniController::class, 'update'])->whereNumber('alumni'); // multipart fallback

        /* ------------------- Admin: Super Admin / Event Manager ------------- */
        Route::middleware('role:super_admin|event_manager')->group(function () {

            // Dashboard statistics
            Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);

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
        });
    });
});

// Simple health probe for load balancers / uptime checks.
Route::get('/health', fn () => response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]));
