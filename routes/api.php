<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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
