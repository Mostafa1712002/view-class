<?php

use App\Modules\Communications\Controllers\ParentCrmController;
use App\Modules\Communications\Controllers\ParentsContactController;
use Illuminate\Support\Facades\Route;

// ==================================================================
// Parents as a contact — Communications (Sprint 9 — Trello #242)
// ==================================================================
// Route names: admin.parents-contact.*  (matches the sidebar gate in
// resources/views/components/sidebar.blade.php — Route::has('admin.parents-contact.index')).
// Each route is gated by canDo via the `permission` middleware AND re-checked
// in the controller/view for manage actions (backend enforcement, not UI-only).
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/parents-contact')
    ->name('admin.parents-contact.')
    ->group(function () {
        Route::get('/', [ParentsContactController::class, 'index'])
            ->middleware('permission:parents_contact.view')->name('index');

        Route::get('export', [ParentsContactController::class, 'export'])
            ->middleware('permission:parents_contact.view')->name('export');

        Route::get('{id}', [ParentsContactController::class, 'show'])
            ->middleware('permission:parents_contact.view')->whereNumber('id')->name('show');
    });

// ==================================================================
// Parent CRM write layer — Communications (Sprint 10 — Trello #269)
// ==================================================================
// Appended (does not modify the read group above). Same role gate; writes are
// gated by `permission:parents_contact.manage` AND re-checked in the
// FormRequest::authorize() (canDo). No new permission slugs are introduced.
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/parents-contact/{parent}')
    ->name('admin.parents-contact.')
    ->whereNumber('parent')
    ->group(function () {
        Route::post('complaints', [ParentCrmController::class, 'storeComplaint'])
            ->middleware('permission:parents_contact.manage')->name('complaints.store');

        Route::post('visits', [ParentCrmController::class, 'storeVisit'])
            ->middleware('permission:parents_contact.manage')->name('visits.store');

        Route::post('calls', [ParentCrmController::class, 'storeCall'])
            ->middleware('permission:parents_contact.manage')->name('calls.store');
    });
