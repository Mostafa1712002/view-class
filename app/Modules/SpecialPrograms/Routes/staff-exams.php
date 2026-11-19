<?php

use App\Modules\SpecialPrograms\Controllers\Staff\ExamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin,school-admin,teacher', 'permission:special_programs.exams'])
    ->prefix('manage/special-programs/{program}')->whereIn('program', ['qiyas', 'tahsili', 'nafis'])
    ->name('manage.special-programs.')->group(function () {
        Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('exams/{id}', [ExamController::class, 'show'])->whereNumber('id')->name('exams.show');
        Route::delete('exams/{id}', [ExamController::class, 'destroy'])->whereNumber('id')->name('exams.destroy');
        Route::get('exams/{id}/export', [ExamController::class, 'export'])->whereNumber('id')->name('exams.export');
        Route::get('bank-stats', [ExamController::class, 'bankStats'])->name('bank-stats');
    });
