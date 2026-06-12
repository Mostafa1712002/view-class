<?php

// ==========================================
// Virtual Classrooms module
// ==========================================

// Staff: manage sessions (super-admin, school-admin, teacher)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/virtual-classes')
    ->name('manage.virtual-classes.')
    ->group(function () {
        Route::get('/', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'index'])->name('index');
        Route::get('create', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'store'])->name('store');
        Route::get('{id}', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'show'])->name('show');
        Route::get('{id}/edit', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'edit'])->name('edit');
        Route::put('{id}', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'update'])->name('update');
        Route::delete('{id}', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'destroy'])->name('destroy');
        Route::post('{id}/cancel', [\App\Modules\VirtualClasses\Controllers\VirtualClassController::class, 'cancel'])->name('cancel');
    });

// Students: read-only list of upcoming sessions
Route::middleware(['auth'])
    ->prefix('my/virtual-classes')
    ->name('my.virtual-classes.')
    ->group(function () {
        Route::get('/', [\App\Modules\VirtualClasses\Controllers\StudentVirtualClassController::class, 'index'])->name('index');
    });
