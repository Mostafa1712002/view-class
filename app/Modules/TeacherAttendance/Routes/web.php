<?php

use App\Modules\TeacherAttendance\Controllers\TeacherAttendanceController;
use Illuminate\Support\Facades\Route;

// ==========================================================================
// Teacher Attendance module — Sprint 10 (#264) — NET-NEW
// Self-contained; require'd once from routes/web.php.
// ==========================================================================

Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/teacher-attendance')
    ->name('admin.teacher-attendance.')
    ->group(function () {
        Route::get('daily', [TeacherAttendanceController::class, 'daily'])
            ->middleware('permission:teacher_attendance.view')->name('daily');
        Route::get('period', [TeacherAttendanceController::class, 'period'])
            ->middleware('permission:teacher_attendance.view')->name('period');
        Route::post('store', [TeacherAttendanceController::class, 'store'])
            ->middleware('permission:teacher_attendance.record_present')->name('store');
        Route::post('message/{teacher}', [TeacherAttendanceController::class, 'message'])
            ->middleware('permission:teacher_attendance.send_message')->whereNumber('teacher')->name('message');
    });
