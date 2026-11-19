<?php

use App\Modules\SpecialPrograms\Controllers\Student\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->prefix('student/programs')->name('student.programs.')
    ->whereIn('program', ['qiyas', 'tahsili', 'nafis'])->group(function () {
        Route::get('report/{program}', [ReportController::class, 'show'])->name('report');
        Route::get('report/{program}/pdf', [ReportController::class, 'pdf'])->name('report.pdf');
    });
