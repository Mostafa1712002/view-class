<?php

use App\Modules\EducationalSites\Controllers\EducationalSiteController;
use Illuminate\Support\Facades\Route;

// ==========================================================================
// Educational Sites module — Sprint 10 (#270) — NET-NEW
// Self-contained; require'd once from routes/web.php.
//
//   DISPLAY  → any authenticated user (students/parents see active cards).
//              Gated by educational_sites.view (default-allow for reads).
//   MANAGEMENT → super/school admins with the educational_sites.* write perms.
// ==========================================================================

// Public-facing card grid (all authenticated users).
Route::middleware(['auth'])
    ->prefix('educational-sites')
    ->name('educational-sites.')
    ->group(function () {
        Route::get('/', [EducationalSiteController::class, 'display'])
            ->middleware('permission:educational_sites.view')->name('display');
    });

// Admin management.
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/educational-sites')
    ->name('admin.educational-sites.')
    ->group(function () {
        Route::get('/', [EducationalSiteController::class, 'index'])
            ->middleware('permission:educational_sites.view')->name('index');
        Route::get('create', [EducationalSiteController::class, 'create'])
            ->middleware('permission:educational_sites.create')->name('create');
        Route::post('/', [EducationalSiteController::class, 'store'])
            ->middleware('permission:educational_sites.create')->name('store');
        Route::get('{id}/edit', [EducationalSiteController::class, 'edit'])
            ->middleware('permission:educational_sites.edit')->whereNumber('id')->name('edit');
        Route::put('{id}', [EducationalSiteController::class, 'update'])
            ->middleware('permission:educational_sites.edit')->whereNumber('id')->name('update');
        Route::delete('{id}', [EducationalSiteController::class, 'destroy'])
            ->middleware('permission:educational_sites.delete')->whereNumber('id')->name('destroy');
        Route::post('{id}/toggle', [EducationalSiteController::class, 'toggle'])
            ->middleware('permission:educational_sites.toggle_active')->whereNumber('id')->name('toggle');
        Route::post('reorder', [EducationalSiteController::class, 'reorder'])
            ->middleware('permission:educational_sites.reorder')->name('reorder');
    });
