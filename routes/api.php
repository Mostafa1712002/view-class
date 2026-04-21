<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use App\Modules\Auth\Controllers\AuthApiController;
use App\Modules\Dashboard\Controllers\DashboardApiController;
use App\Modules\Profile\Controllers\ProfileApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Spec-aligned JWT auth endpoints (card 3). Rate limited per spec (5/min).
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthApiController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('refresh', [AuthApiController::class, 'refresh'])->middleware('throttle:auth-login');
    Route::post('logout', [AuthApiController::class, 'logout']);
    Route::get('me', [AuthApiController::class, 'me'])->middleware('jwt');
});

// Profile endpoints (card 6). JWT-authenticated.
Route::middleware('jwt')->prefix('users/me')->group(function () {
    Route::patch('/', [ProfileApiController::class, 'update']);
    Route::patch('password', [ProfileApiController::class, 'changePassword']);
    Route::post('avatar', [ProfileApiController::class, 'updateAvatar']);
});

// Dashboard stats endpoints (card 5). JWT-authenticated.
Route::middleware('jwt')->prefix('dashboard')->group(function () {
    Route::get('stats', [DashboardApiController::class, 'stats']);
    Route::get('interaction-rates', [DashboardApiController::class, 'interactionRates']);
    Route::get('content-stats', [DashboardApiController::class, 'contentStats']);
    Route::get('various-stats', [DashboardApiController::class, 'variousStats']);
    Route::get('weekly-absence', [DashboardApiController::class, 'weeklyAbsence']);
});

// Legacy Sprint 9 Sanctum routes — kept for backward compatibility.
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('student')->group(function () {
        Route::get('/dashboard', [StudentApiController::class, 'dashboard']);
        Route::get('/grades', [StudentApiController::class, 'grades']);
        Route::get('/attendance', [StudentApiController::class, 'attendance']);
        Route::get('/schedule', [StudentApiController::class, 'schedule']);
        Route::get('/weekly-plans', [StudentApiController::class, 'weeklyPlans']);
    });
});
