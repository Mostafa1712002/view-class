<?php

use App\Modules\SpecialPrograms\Controllers\Staff\TeacherReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher', 'permission:special_programs.reports'])
    ->prefix('manage/special-programs/teacher-reports')->name('manage.special-programs.teacher-reports.')->group(function () {
        Route::get('/', [TeacherReportController::class, 'index'])->name('index');
        Route::get('exams/{id}', [TeacherReportController::class, 'exam'])->whereNumber('id')->name('exam');
        Route::get('exams/{id}/export', [TeacherReportController::class, 'export'])->whereNumber('id')->name('export');
    });
