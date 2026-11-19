<?php

use App\Modules\RawaniHub\Controllers\Admin\DirectoryController;
use App\Modules\RawaniHub\Controllers\Admin\PackagesController;
use App\Modules\RawaniHub\Controllers\Admin\PaymentsController;
use App\Modules\RawaniHub\Controllers\Admin\PeopleController;
use App\Modules\RawaniHub\Controllers\Admin\PlatformsController;
use App\Modules\RawaniHub\Controllers\Admin\SubscriptionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin'])->prefix('admin/hub')->name('admin.hub.')->group(function () {
    Route::get('platforms', [PlatformsController::class, 'index'])->name('platforms.index');
    Route::get('platforms/create', [PlatformsController::class, 'create'])->name('platforms.create');
    Route::post('platforms', [PlatformsController::class, 'store'])->name('platforms.store');
    Route::get('platforms/{platform}/edit', [PlatformsController::class, 'edit'])->whereNumber('platform')->name('platforms.edit');
    Route::put('platforms/{platform}', [PlatformsController::class, 'update'])->whereNumber('platform')->name('platforms.update');
    Route::post('platforms/{platform}/test', [PlatformsController::class, 'test'])->whereNumber('platform')->name('platforms.test');
    Route::post('platforms/{platform}/secret', [PlatformsController::class, 'secret'])->whereNumber('platform')->name('platforms.secret');

    Route::get('packages', [PackagesController::class, 'index'])->name('packages.index');
    Route::get('packages/create', [PackagesController::class, 'create'])->name('packages.create');
    Route::post('packages', [PackagesController::class, 'store'])->name('packages.store');
    Route::get('packages/{package}/edit', [PackagesController::class, 'edit'])->whereNumber('package')->name('packages.edit');
    Route::put('packages/{package}', [PackagesController::class, 'update'])->whereNumber('package')->name('packages.update');
    Route::delete('packages/{package}', [PackagesController::class, 'destroy'])->whereNumber('package')->name('packages.destroy');
    Route::patch('packages/{package}/toggle', [PackagesController::class, 'toggle'])->whereNumber('package')->name('packages.toggle');

    Route::get('subscriptions', [SubscriptionsController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/grant', [SubscriptionsController::class, 'grantForm'])->name('subscriptions.grant');
    Route::post('subscriptions/grant', [SubscriptionsController::class, 'grant'])->name('subscriptions.grant.store');
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionsController::class, 'cancel'])->whereNumber('subscription')->name('subscriptions.cancel');

    Route::get('payments', [PaymentsController::class, 'index'])->name('payments.index');
    Route::post('payments/{payment}/check', [PaymentsController::class, 'check'])->whereNumber('payment')->name('payments.check');
    Route::post('payments/{payment}/refund', [PaymentsController::class, 'refund'])->whereNumber('payment')->name('payments.refund');
    Route::get('payments/{payment}/receipt', [PaymentsController::class, 'receipt'])->whereNumber('payment')->name('payments.receipt');

    Route::get('people/{user}', [PeopleController::class, 'show'])->whereNumber('user')->name('people.show');
    Route::prefix('accounts/{account}')->whereNumber('account')->name('accounts.')->group(function () {
        Route::post('retry', [PeopleController::class, 'retry'])->name('retry');
        Route::post('toggle', [PeopleController::class, 'toggle'])->name('toggle');
        Route::post('reset-password', [PeopleController::class, 'resetPassword'])->name('reset');
        Route::post('reveal', [PeopleController::class, 'reveal'])->name('reveal');
        Route::post('link', [PeopleController::class, 'link'])->name('link');
    });

    Route::get('directory', [DirectoryController::class, 'index'])->name('directory.index');
    Route::prefix('accounts/{account}')->whereNumber('account')->name('accounts.')->group(function () {
        Route::get('link', [DirectoryController::class, 'linkForm'])->name('link.form');
        Route::post('create-person', [DirectoryController::class, 'createPerson'])->name('create_person');
        Route::delete('link', [DirectoryController::class, 'unlink'])->name('unlink');
    });
});
