<?php

use App\Modules\Announcements\Controllers\AnnouncementController;
use App\Modules\Announcements\Controllers\UserAnnouncementController;
use Illuminate\Support\Facades\Route;

// ==========================================
// Announcements module (Sprint 9 — Task 2)
// ==========================================

// Admin / staff: manage announcements. Each route gated by its permission key
// (canDo via the `permission` middleware) AND re-checked in the controller for
// write actions (backend enforcement, not UI-only).
Route::middleware(['auth'])
    ->prefix('admin/announcements')
    ->name('admin.announcements.')
    ->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])
            ->middleware('permission:announcements.view')->name('index');

        Route::get('create', [AnnouncementController::class, 'create'])
            ->middleware('permission:announcements.create')->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])
            ->middleware('permission:announcements.create')->name('store');

        Route::get('{id}', [AnnouncementController::class, 'show'])
            ->middleware('permission:announcements.view')->whereNumber('id')->name('show');

        Route::get('{id}/edit', [AnnouncementController::class, 'edit'])
            ->middleware('permission:announcements.edit')->whereNumber('id')->name('edit');
        Route::put('{id}', [AnnouncementController::class, 'update'])
            ->middleware('permission:announcements.edit')->whereNumber('id')->name('update');

        Route::delete('{id}', [AnnouncementController::class, 'destroy'])
            ->middleware('permission:announcements.delete')->whereNumber('id')->name('destroy');

        Route::post('{id}/activate', [AnnouncementController::class, 'activate'])
            ->middleware('permission:announcements.publish')->whereNumber('id')->name('activate');
        Route::post('{id}/stop', [AnnouncementController::class, 'stop'])
            ->middleware('permission:announcements.publish')->whereNumber('id')->name('stop');

        Route::post('{id}/duplicate', [AnnouncementController::class, 'duplicate'])
            ->middleware('permission:announcements.create')->whereNumber('id')->name('duplicate');

        Route::get('{id}/read-log', [AnnouncementController::class, 'readLog'])
            ->middleware('permission:announcements.read_log')->whereNumber('id')->name('read-log');
    });

// End-users: view / confirm announcements targeted to them.
Route::middleware(['auth'])
    ->prefix('announcements')
    ->name('announcements.')
    ->group(function () {
        Route::get('/', [UserAnnouncementController::class, 'index'])->name('index');
        Route::get('{id}', [UserAnnouncementController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('{id}/confirm', [UserAnnouncementController::class, 'confirm'])->whereNumber('id')->name('confirm');
        Route::post('{id}/dismiss', [UserAnnouncementController::class, 'dismiss'])->whereNumber('id')->name('dismiss');
    });
