<?php

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
