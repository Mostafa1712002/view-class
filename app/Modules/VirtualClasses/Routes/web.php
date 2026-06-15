<?php

use App\Modules\VirtualClasses\Controllers\StudentVirtualClassController;
use App\Modules\VirtualClasses\Controllers\VirtualClassController;

// ==========================================
// Virtual Classrooms module
// ==========================================

// Staff: manage sessions (super-admin, school-admin, teacher).
// Routes carry `permission:` middleware; controllers re-check canDo() (fail closed).
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/virtual-classes')
    ->name('manage.virtual-classes.')
    ->group(function () {
        // Read
        Route::middleware('permission:virtual_classes.view')->group(function () {
            Route::get('/', [VirtualClassController::class, 'index'])->name('index');
            Route::get('{id}', [VirtualClassController::class, 'show'])->whereNumber('id')->name('show');
        });

        // Create
        Route::middleware('permission:virtual_classes.create')->group(function () {
            Route::get('create', [VirtualClassController::class, 'create'])->name('create');
            Route::post('/', [VirtualClassController::class, 'store'])->name('store');
        });

        // Edit / cancel
        Route::middleware('permission:virtual_classes.edit')->group(function () {
            Route::get('{id}/edit', [VirtualClassController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('{id}', [VirtualClassController::class, 'update'])->whereNumber('id')->name('update');
            Route::post('{id}/cancel', [VirtualClassController::class, 'cancel'])->whereNumber('id')->name('cancel');
        });

        // Delete
        Route::middleware('permission:virtual_classes.delete')
            ->delete('{id}', [VirtualClassController::class, 'destroy'])->whereNumber('id')->name('destroy');

        // Start (host)
        Route::middleware('permission:virtual_classes.start')
            ->post('{id}/start', [VirtualClassController::class, 'start'])->whereNumber('id')->name('start');

        // Attendance: view / export
        Route::middleware('permission:virtual_classes.view_attendance')->group(function () {
            Route::get('{id}/attendance', [VirtualClassController::class, 'attendance'])->whereNumber('id')->name('attendance');
            Route::get('{id}/attendance/export', [VirtualClassController::class, 'exportAttendance'])->whereNumber('id')->name('attendance.export');
        });

        // Recalc attendance
        Route::middleware('permission:virtual_classes.recalc_attendance')
            ->post('{id}/attendance/recalc', [VirtualClassController::class, 'recalcAttendance'])->whereNumber('id')->name('attendance.recalc');

        // Clear cache
        Route::middleware('permission:virtual_classes.clear_cache')
            ->post('{id}/cache/clear', [VirtualClassController::class, 'clearCache'])->whereNumber('id')->name('cache.clear');
    });

// Students: read-only list of upcoming sessions + join (gated by enrollment, NOT canDo).
Route::middleware(['auth'])
    ->prefix('my/virtual-classes')
    ->name('my.virtual-classes.')
    ->group(function () {
        Route::get('/', [StudentVirtualClassController::class, 'index'])->name('index');
        Route::post('{id}/join', [StudentVirtualClassController::class, 'join'])->whereNumber('id')->name('join');
    });
