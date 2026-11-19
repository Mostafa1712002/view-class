<?php

use App\Modules\SpecialPrograms\Controllers\Student\AttemptController;
use App\Modules\SpecialPrograms\Controllers\Student\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->prefix('student/programs')->name('student.programs.')->group(function () {
    Route::get('/', [ProgramController::class, 'index'])->name('index');
    Route::get('exams/{id}', [ProgramController::class, 'exam'])->whereNumber('id')->name('exam');
    Route::get('exams/{id}/final', [ProgramController::class, 'final'])->whereNumber('id')->name('final');
    Route::post('exams/{examId}/subjects/{subjectId}/start', [AttemptController::class, 'start'])
        ->whereNumber(['examId', 'subjectId'])->name('start');

    Route::prefix('attempts/{id}')->whereNumber('id')->group(function () {
        Route::get('/', [AttemptController::class, 'take'])->name('take');
        Route::post('answer', [AttemptController::class, 'answer'])->name('answer');
        Route::get('heartbeat', [AttemptController::class, 'heartbeat'])->name('heartbeat');
        Route::post('exit', [AttemptController::class, 'exit'])->name('exit');
        Route::post('finish', [AttemptController::class, 'finish'])->name('finish');
        Route::get('result', [AttemptController::class, 'result'])->name('result');
    });
});
