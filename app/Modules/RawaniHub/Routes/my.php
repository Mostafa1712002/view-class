<?php

use App\Modules\RawaniHub\Controllers\My\MyPackagesController;
use App\Modules\RawaniHub\Controllers\My\RegisterController;
use Illuminate\Support\Facades\Route;

// Public «سجّل واشترِ» (design Decision Q1): a parent registers, then lands on «باقاتي».
Route::middleware(['guest', 'throttle:6,1'])->group(function () {
    Route::get('register', [RegisterController::class, 'show'])->name('hub.register');
    Route::post('register', [RegisterController::class, 'store'])->name('hub.register.store');
});

// «باقاتي» (students and parents). `my/packages/accounts/{a}/open` belongs to Phase 5.
Route::middleware(['auth', 'role:student,parent'])->prefix('my/packages')->name('my.packages.')->group(function () {
    Route::get('/', [MyPackagesController::class, 'index'])->name('index');
    Route::get('children/create', [MyPackagesController::class, 'childForm'])->name('children.create');
    Route::post('children', [MyPackagesController::class, 'childStore'])->name('children.store');
    Route::post('children/{child}/complete', [MyPackagesController::class, 'complete'])->whereNumber('child')->name('children.complete');
    Route::get('return/{payment}', [MyPackagesController::class, 'return'])->whereNumber('payment')->name('return');
    Route::get('return/{payment}/status', [MyPackagesController::class, 'status'])->whereNumber('payment')->name('return.status');
    Route::get('{package}/buy', [MyPackagesController::class, 'buy'])->whereNumber('package')->name('buy');
    Route::post('{package}/checkout', [MyPackagesController::class, 'checkout'])->whereNumber('package')->name('checkout');
});
