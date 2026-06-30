<?php

use App\Modules\Qr\Controllers\QrCardController;
use App\Modules\Qr\Controllers\QrDeviceController;
use App\Modules\Qr\Controllers\QrGroupController;
use App\Modules\Qr\Controllers\QrLinkController;
use App\Modules\Qr\Controllers\QrScannerController;
use Illuminate\Support\Facades\Route;

// ==========================================================================
// QR Attendance Services module — Sprint 10 (#265) — NET-NEW
// Self-contained; require'd once from routes/web.php.
// ==========================================================================

Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/qr')
    ->name('admin.qr.')
    ->group(function () {
        // Cards
        Route::get('cards', [QrCardController::class, 'index'])
            ->middleware('permission:qr.view')->name('cards.index');
        Route::post('cards/generate', [QrCardController::class, 'generate'])
            ->middleware('permission:qr.create_card')->name('cards.generate');
        Route::post('cards/{card}/toggle', [QrCardController::class, 'toggle'])
            ->middleware('permission:qr.create_card')->whereNumber('card')->name('cards.toggle');
        Route::post('cards/{card}/regenerate', [QrCardController::class, 'regenerate'])
            ->middleware('permission:qr.create_card')->whereNumber('card')->name('cards.regenerate');
        Route::get('cards/print', [QrCardController::class, 'print'])
            ->middleware('permission:qr.print_card')->name('cards.print');

        // Scanner + scan endpoint + log + day-close
        Route::get('scanner', [QrScannerController::class, 'scanner'])
            ->middleware('permission:qr.scan')->name('scanner');
        Route::post('scan', [QrScannerController::class, 'scan'])
            ->middleware('permission:qr.scan')->name('scan');
        Route::get('log', [QrScannerController::class, 'log'])
            ->middleware('permission:qr.view_log')->name('log');
        Route::post('close-day', [QrScannerController::class, 'closeDay'])
            ->middleware('permission:qr.close_day')->name('close-day');

        // Groups
        Route::get('groups', [QrGroupController::class, 'index'])
            ->middleware('permission:qr.view')->name('groups.index');
        Route::get('groups/create', [QrGroupController::class, 'create'])
            ->middleware('permission:qr.group_create')->name('groups.create');
        Route::post('groups', [QrGroupController::class, 'store'])
            ->middleware('permission:qr.group_create')->name('groups.store');
        Route::get('groups/{group}/edit', [QrGroupController::class, 'edit'])
            ->middleware('permission:qr.group_edit')->whereNumber('group')->name('groups.edit');
        Route::put('groups/{group}', [QrGroupController::class, 'update'])
            ->middleware('permission:qr.group_edit')->whereNumber('group')->name('groups.update');
        Route::delete('groups/{group}', [QrGroupController::class, 'destroy'])
            ->middleware('permission:qr.group_delete')->whereNumber('group')->name('groups.destroy');

        // Link students to attendance groups / issue cards
        Route::get('link', [QrLinkController::class, 'index'])
            ->middleware('permission:qr.link_students')->name('link.index');
        Route::post('link', [QrLinkController::class, 'assign'])
            ->middleware('permission:qr.link_students')->name('link.assign');

        // IoT scanner devices registry
        Route::get('devices', [QrDeviceController::class, 'index'])
            ->middleware('permission:qr.link_devices')->name('devices.index');
        Route::post('devices', [QrDeviceController::class, 'store'])
            ->middleware('permission:qr.link_devices')->name('devices.store');
        Route::post('devices/{device}/toggle', [QrDeviceController::class, 'toggle'])
            ->middleware('permission:qr.link_devices')->whereNumber('device')->name('devices.toggle');
        Route::post('devices/{device}/regenerate', [QrDeviceController::class, 'regenerate'])
            ->middleware('permission:qr.link_devices')->whereNumber('device')->name('devices.regenerate');
        Route::delete('devices/{device}', [QrDeviceController::class, 'destroy'])
            ->middleware('permission:qr.link_devices')->whereNumber('device')->name('devices.destroy');
    });
