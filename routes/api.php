<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use App\Modules\Auth\Controllers\AuthApiController;
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
