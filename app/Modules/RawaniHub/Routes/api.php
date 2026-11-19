<?php

use App\Modules\RawaniHub\Controllers\MoyasarWebhookController;
use Illuminate\Support\Facades\Route;

// Loaded from routes/api.php (prefix /api, no CSRF) only when the hub is enabled.
Route::post('hub/moyasar/webhook', MoyasarWebhookController::class)->middleware('throttle:60,1')->name('hub.moyasar.webhook');
