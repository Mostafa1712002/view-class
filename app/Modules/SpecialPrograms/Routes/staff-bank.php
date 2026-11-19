<?php

use App\Modules\SpecialPrograms\Controllers\Staff\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin,school-admin,teacher', 'permission:special_programs.questions'])
    ->prefix('manage/special-programs/{program}/bank')->name('manage.special-programs.bank.')
    ->whereIn('program', ['qiyas', 'tahsili', 'nafis'])->group(function () {
        Route::get('/', [BankController::class, 'index'])->name('index');
        Route::get('passages', [BankController::class, 'passages'])->name('passages');
        Route::get('create', [BankController::class, 'create'])->name('create');
        Route::get('passages/create', [BankController::class, 'passagesCreate'])->name('passages.create');
        Route::post('/', [BankController::class, 'store'])->name('store');
        Route::post('passages', [BankController::class, 'passagesStore'])->name('passages.store');
        Route::get('passages/{id}/edit', [BankController::class, 'passagesEdit'])->whereNumber('id')->name('passages.edit');
        Route::put('passages/{id}', [BankController::class, 'passagesUpdate'])->whereNumber('id')->name('passages.update');
        Route::delete('passages/{id}', [BankController::class, 'passagesDestroy'])->whereNumber('id')->name('passages.destroy');
        Route::get('{id}', [BankController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('{id}/edit', [BankController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('{id}', [BankController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}', [BankController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
