<?php

use App\Modules\SpecialPrograms\Controllers\Staff\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin,school-admin,teacher', 'permission:special_programs.reports'])
    ->prefix('manage/special-programs/{program}')->whereIn('program', ['qiyas', 'tahsili', 'nafis'])
    ->name('manage.special-programs.')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
        Route::get('students/{studentId}/report', [ReportController::class, 'student'])->whereNumber('studentId')->name('student-report');
        Route::get('students/{studentId}/report/pdf', [ReportController::class, 'studentPdf'])->whereNumber('studentId')->name('student-report.pdf');
    });
