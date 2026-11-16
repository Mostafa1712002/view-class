<?php

use App\Modules\Counseling\Controllers\CounselingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('manage/counseling')->name('manage.counseling.')->group(function () {
    Route::get('/', [CounselingController::class, 'index'])->name('index');
    Route::get('create', [CounselingController::class, 'create'])->name('create');
    Route::post('/', [CounselingController::class, 'store'])->name('store');
    Route::get('{id}', [CounselingController::class, 'show'])->whereNumber('id')->name('show');
    Route::get('{id}/edit', [CounselingController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('{id}', [CounselingController::class, 'update'])->whereNumber('id')->name('update');
    Route::patch('{id}/status', [CounselingController::class, 'status'])->whereNumber('id')->name('status');
    Route::post('{id}/notes', [CounselingController::class, 'note'])->whereNumber('id')->name('notes.store');
    Route::delete('{id}', [CounselingController::class, 'destroy'])->whereNumber('id')->name('destroy');
});
