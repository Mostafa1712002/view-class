<?php

// ==========================================
// Special Education module
// ==========================================

Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/special-education')
    ->name('manage.special-education.')
    ->group(function () {

        // SE student CRUD
        Route::get('/', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'index'])->name('index');
        Route::get('create', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'store'])->name('store');
        Route::get('{id}/edit', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('{id}', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'destroy'])->whereNumber('id')->name('destroy');

        // Student detail (plans + notes)
        Route::get('{id}', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'show'])->whereNumber('id')->name('show');

        // Plans
        Route::post('{id}/plans', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'plansStore'])->whereNumber('id')->name('plans.store');
        Route::delete('{id}/plans/{planId}', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'plansDestroy'])->whereNumber('id')->whereNumber('planId')->name('plans.destroy');

        // Notes
        Route::post('{id}/notes', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'notesStore'])->whereNumber('id')->name('notes.store');
        Route::delete('{id}/notes/{noteId}', [\App\Modules\SpecialEducation\Controllers\SpecialEducationController::class, 'notesDestroy'])->whereNumber('id')->whereNumber('noteId')->name('notes.destroy');
    });
