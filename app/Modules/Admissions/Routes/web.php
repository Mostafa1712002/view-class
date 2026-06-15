<?php

use App\Modules\Admissions\Controllers\AdmissionController;
use App\Modules\Admissions\Controllers\AdmissionSettingsController;
use App\Modules\Admissions\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

// ==========================================================================
// Admissions / Registration module — Sprint 10 (#268) — NET-NEW
// Self-contained; require'd once from routes/web.php.
// ==========================================================================

// ── Public (guest) registration — school comes from the LINK, no auth ──────
Route::middleware(['web', 'throttle:20,1'])
    ->prefix('register')
    ->name('admissions.public.')
    ->group(function () {
        Route::get('school/{school}', [PublicRegistrationController::class, 'school'])
            ->whereNumber('school')->name('school');
        Route::post('school/{school}', [PublicRegistrationController::class, 'store'])
            ->whereNumber('school')->name('store');
        Route::get('school/{school}/success/{code}', [PublicRegistrationController::class, 'success'])
            ->whereNumber('school')->name('success');
        Route::get('company/{company}', [PublicRegistrationController::class, 'company'])
            ->whereNumber('company')->name('company');
    });

// ── Staff side — auth + role gate + per-action permission:admissions.* ──────
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/admissions')
    ->name('admissions.')
    ->group(function () {
        Route::get('/', [AdmissionController::class, 'index'])
            ->middleware('permission:admissions.view')->name('index');

        // settings surfaces
        Route::get('settings/schools', [AdmissionSettingsController::class, 'schoolSettings'])
            ->middleware('permission:admissions.edit_school_settings')->name('settings.schools');
        Route::post('settings/schools', [AdmissionSettingsController::class, 'saveSchoolSettings'])
            ->middleware('permission:admissions.edit_school_settings')->name('settings.schools.save');
        Route::get('settings/form', [AdmissionSettingsController::class, 'formSettings'])
            ->middleware('permission:admissions.edit_settings')->name('settings.form');
        Route::post('settings/form', [AdmissionSettingsController::class, 'saveFormSettings'])
            ->middleware('permission:admissions.edit_settings')->name('settings.form.save');
        Route::get('info', [AdmissionSettingsController::class, 'infoIndex'])
            ->middleware('permission:admissions.edit_info')->name('info.index');
        Route::get('info/{id}/edit', [AdmissionSettingsController::class, 'infoEdit'])
            ->middleware('permission:admissions.edit_info')->whereNumber('id')->name('info.edit');
        Route::put('info/{id}', [AdmissionSettingsController::class, 'infoUpdate'])
            ->middleware('permission:admissions.edit_info')->whereNumber('id')->name('info.update');

        // export (filtered)
        Route::get('export', [AdmissionController::class, 'export'])
            ->middleware('permission:admissions.export')->name('export');

        // per-application actions
        Route::get('{id}', [AdmissionController::class, 'show'])
            ->middleware('permission:admissions.view')->whereNumber('id')->name('show');
        Route::get('{id}/edit', [AdmissionController::class, 'edit'])
            ->middleware('permission:admissions.edit')->whereNumber('id')->name('edit');
        Route::put('{id}', [AdmissionController::class, 'update'])
            ->middleware('permission:admissions.edit')->whereNumber('id')->name('update');
        Route::delete('{id}', [AdmissionController::class, 'destroy'])
            ->middleware('permission:admissions.delete')->whereNumber('id')->name('destroy');
        Route::post('{id}/status', [AdmissionController::class, 'changeStatus'])
            ->middleware('permission:admissions.change_status')->whereNumber('id')->name('status');
        Route::post('{id}/schedule', [AdmissionController::class, 'schedule'])
            ->middleware('permission:admissions.schedule')->whereNumber('id')->name('schedule');
        Route::post('{id}/message', [AdmissionController::class, 'message'])
            ->middleware('permission:admissions.change_status')->whereNumber('id')->name('message');
        Route::post('{id}/convert', [AdmissionController::class, 'convert'])
            ->middleware('permission:admissions.convert_to_student')->whereNumber('id')->name('convert');
        Route::get('{id}/print', [AdmissionController::class, 'print'])
            ->middleware('permission:admissions.view')->whereNumber('id')->name('print');
    });
