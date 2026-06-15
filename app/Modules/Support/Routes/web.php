<?php

// ==========================================
// Support / Tickets module (Trello #267)
// ==========================================
//
// Gating model (CLAUDE.md + design.md §2):
//   - User routes (`my/support`) are NOT permission-gated: any authenticated
//     user may open and follow their OWN tickets. Ownership is enforced in the
//     controller (created_by === auth id). Attachments here are own-ticket only.
//   - Admin routes (`admin/support`) keep the coarse role gate AND add the
//     fine-grained `permission:support.*` (CheckPermission → canDo → 403) per
//     action. Reads gate on support.view; every write gates on its action slug.
//   - The 9 support.* slugs are the fixed seeded vocabulary; reopen reuses
//     change_status, export (if added) reuses view.

use App\Modules\Support\Controllers\AdminSupportController;
use App\Modules\Support\Controllers\UserSupportController;

// ── User: own tickets ────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('my/support')->name('my.support.')->group(function () {
    Route::get('/', [UserSupportController::class, 'index'])->name('index');
    Route::get('/create', [UserSupportController::class, 'create'])->name('create');
    Route::post('/', [UserSupportController::class, 'store'])->name('store');
    Route::get('/{ticket}', [UserSupportController::class, 'show'])->name('show');
    Route::get('/{ticket}/attachment', [UserSupportController::class, 'attachment'])->name('attachment');
    Route::post('/{ticket}/reply', [UserSupportController::class, 'reply'])->name('reply');
});

// ── Admin: ticket management ─────────────────────────────────────────────────
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/support')
    ->name('admin.support.')
    ->group(function () {
        Route::get('/', [AdminSupportController::class, 'index'])
            ->middleware('permission:support.view')->name('index');
        Route::get('/{ticket}', [AdminSupportController::class, 'show'])
            ->middleware('permission:support.view')->name('show');
        Route::get('/{ticket}/attachment', [AdminSupportController::class, 'attachment'])
            ->middleware('permission:support.view_attachments')->name('attachment');
        Route::post('/{ticket}/reply', [AdminSupportController::class, 'reply'])
            ->middleware('permission:support.reply')->name('reply');
        Route::post('/{ticket}/assign', [AdminSupportController::class, 'assign'])
            ->middleware('permission:support.assign')->name('assign');
        Route::post('/{ticket}/status', [AdminSupportController::class, 'updateStatus'])
            ->middleware('permission:support.change_status')->name('updateStatus');
        Route::post('/{ticket}/close', [AdminSupportController::class, 'close'])
            ->middleware('permission:support.close')->name('close');
        Route::post('/{ticket}/reopen', [AdminSupportController::class, 'reopen'])
            ->middleware('permission:support.change_status')->name('reopen');
        Route::delete('/{ticket}', [AdminSupportController::class, 'destroy'])
            ->middleware('permission:support.delete')->name('destroy');
    });
