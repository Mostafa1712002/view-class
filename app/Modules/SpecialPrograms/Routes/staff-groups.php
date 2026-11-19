<?php

use App\Modules\SpecialPrograms\Controllers\Staff\GroupController;
use App\Modules\SpecialPrograms\Controllers\Staff\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin,school-admin,teacher', 'permission:special_programs.manage'])
    ->prefix('manage/special-programs/{program}')->whereIn('program', ['qiyas', 'tahsili', 'nafis'])
    ->name('manage.special-programs.')->group(function () {
        Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
        Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{id}', [GroupController::class, 'show'])->whereNumber('id')->name('groups.show');
        Route::get('groups/{id}/edit', [GroupController::class, 'edit'])->whereNumber('id')->name('groups.edit');
        Route::put('groups/{id}', [GroupController::class, 'update'])->whereNumber('id')->name('groups.update');
        Route::delete('groups/{id}', [GroupController::class, 'destroy'])->whereNumber('id')->name('groups.destroy');
        Route::post('groups/{id}/members', [GroupController::class, 'storeMembers'])->whereNumber('id')->name('groups.members.store');
        Route::delete('groups/{id}/members/{studentId}', [GroupController::class, 'destroyMember'])->whereNumber(['id', 'studentId'])->name('groups.members.destroy');

        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::delete('teachers/{id}', [TeacherController::class, 'destroy'])->whereNumber('id')->name('teachers.destroy');
    });
