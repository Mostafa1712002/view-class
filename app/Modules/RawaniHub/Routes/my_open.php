<?php

use App\Modules\RawaniHub\Controllers\My\AccountOpenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student,parent'])
    ->get('my/packages/accounts/{account}/open', AccountOpenController::class)
    ->whereNumber('account')->name('my.packages.accounts.open');
