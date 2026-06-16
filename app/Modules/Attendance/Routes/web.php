<?php

use App\Modules\Attendance\Controllers\FollowUpController;
use App\Modules\Attendance\Controllers\ReportExportController;
use App\Modules\Attendance\Controllers\ReportsController;
use App\Modules\Attendance\Controllers\StudentAttendanceController;
use Illuminate\Support\Facades\Route;

// ==========================================================================
// Student Attendance module — Sprint 10 (#261 / #262 / #263)
// Self-contained: the ONLY edit to routes/web.php is the require at its EOF.
// Route NAMES are distinct from the legacy admin.attendance.* set
// (admin.attendance.index/store/daily-report/... in routes/web.php) — these
// live under admin.student-attendance.* and admin.attendance.reports.*
// Every route is role-gated (staff only => non-vacuous 403) AND permission-gated.
// ==========================================================================

Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/attendance')
    ->name('admin.student-attendance.')
    ->group(function () {
        // #261 — boards
        Route::get('students/daily', [StudentAttendanceController::class, 'daily'])
            ->middleware('permission:attendance.view')->name('daily');
        Route::get('students/period', [StudentAttendanceController::class, 'period'])
            ->middleware('permission:attendance.view')->name('period');

        // #261 — writes
        Route::post('students/store', [StudentAttendanceController::class, 'store'])
            ->middleware('permission:attendance.record_present')->name('store');
        Route::post('students/bulk', [StudentAttendanceController::class, 'bulk'])
            ->middleware('permission:attendance.bulk_present')->name('bulk');
        Route::post('students/{attendance}/note', [StudentAttendanceController::class, 'addNote'])
            ->middleware('permission:attendance.add_note')->whereNumber('attendance')->name('note');
        Route::post('students/{attendance}/excuse', [StudentAttendanceController::class, 'addExcuse'])
            ->middleware('permission:attendance.add_excuse')->whereNumber('attendance')->name('excuse');

        // #262 — follow-up + user reports
        Route::get('follow-up', [FollowUpController::class, 'index'])
            ->middleware('permission:attendance.view')->name('follow-up');
        Route::post('follow-up/{attendance}/notify', [FollowUpController::class, 'notify'])
            ->middleware('permission:attendance.notify_parent')->whereNumber('attendance')->name('follow-up.notify');
        Route::get('user-reports', [FollowUpController::class, 'userReports'])
            ->middleware('permission:attendance.view')->name('user-reports');
        Route::post('user-reports/send', [FollowUpController::class, 'sendUserReports'])
            ->middleware('permission:attendance.notify_parent')->name('user-reports.send');
    });

// #263 — reports (read-only screens; gated by attendance.view_reports)
Route::middleware(['auth', 'role:super-admin,school-admin', 'permission:attendance.view_reports'])
    ->prefix('admin/attendance/reports')
    ->name('admin.attendance.reports.')
    ->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('status', [ReportsController::class, 'attendanceStatus'])->name('status');
        Route::get('day-absence', [ReportsController::class, 'dayAbsence'])->name('day-absence');
        Route::get('period-absence', [ReportsController::class, 'periodAbsence'])->name('period-absence');
        Route::get('late', [ReportsController::class, 'late'])->name('late');
        Route::get('aggregate', [ReportsController::class, 'aggregate'])->name('aggregate');
        Route::get('behavior', [ReportsController::class, 'behavior'])->name('behavior');

        // #273 — export / print / PDF (pdf|excel|csv) for each report.
        // Gated by pdf_export inside the controller (fail-closed for non-admins).
        Route::get('export/{report}', [ReportExportController::class, 'export'])->name('export');
    });
