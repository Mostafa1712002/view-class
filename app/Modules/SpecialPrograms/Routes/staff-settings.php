<?php

use App\Modules\SpecialPrograms\Controllers\Staff\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('manage/special-programs')->name('manage.special-programs.')->group(function () {
    // Global bands / target grades / weeks / school-edit switches: super-admin only, program comes as ?program=.
    Route::get('settings/global', [SettingsController::class, 'global'])->middleware('role:super-admin')->name('settings.global');
    Route::put('settings/global', [SettingsController::class, 'globalUpdate'])->middleware('role:super-admin')->name('settings.global.update');

    Route::middleware('permission:special_programs.settings')->group(function () {
        Route::get('{program}/settings', [SettingsController::class, 'index'])->whereIn('program', ['qiyas', 'tahsili', 'nafis'])->name('settings.index');
        Route::put('{program}/settings', [SettingsController::class, 'update'])->whereIn('program', ['qiyas', 'tahsili', 'nafis'])->name('settings.update');
    });
});
