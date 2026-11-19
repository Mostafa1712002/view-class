<?php

use App\Modules\SpecialPrograms\Controllers\Staff\EnrollmentController;
use App\Modules\SpecialPrograms\Controllers\Staff\HubController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('manage/special-programs')
    ->name('manage.special-programs.')->group(function () {
        Route::get('/', [HubController::class, 'index'])->middleware('permission:special_programs.view')->name('index');

        Route::prefix('{program}')->where(['program' => 'qiyas|tahsili|nafis'])->group(function () {
            Route::get('/', [HubController::class, 'program'])->middleware('permission:special_programs.view')->name('program');
            // manage, not view: the list shows every enrolled student of the school with national ids.
            Route::get('enrollments', [EnrollmentController::class, 'index'])->middleware('permission:special_programs.manage')->name('enrollments.index');
            Route::get('enrollments/create', [EnrollmentController::class, 'create'])->middleware('permission:special_programs.manage')->name('enrollments.create');
            Route::post('enrollments', [EnrollmentController::class, 'store'])->middleware('permission:special_programs.manage')->name('enrollments.store');
            Route::delete('enrollments/{id}', [EnrollmentController::class, 'destroy'])->whereNumber('id')->middleware('permission:special_programs.manage')->name('enrollments.destroy');
        });
    });
