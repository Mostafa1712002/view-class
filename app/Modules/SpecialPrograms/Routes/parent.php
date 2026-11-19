<?php

use App\Modules\SpecialPrograms\Controllers\Parent\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:parent'])->prefix('parent/programs')->name('parent.programs.')
    ->whereIn('program', ['qiyas', 'tahsili', 'nafis'])->group(function () {
        Route::get('/', [ProgramController::class, 'index'])->name('index');
        Route::get('{childId}/{program}', [ProgramController::class, 'report'])->whereNumber('childId')->name('report');
        Route::get('{childId}/{program}/pdf', [ProgramController::class, 'pdf'])->whereNumber('childId')->name('report.pdf');
    });
