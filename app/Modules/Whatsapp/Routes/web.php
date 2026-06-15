<?php

use App\Modules\Whatsapp\Controllers\WhatsappSendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp Send / Compose (Trello #237)
|--------------------------------------------------------------------------
| The compose experience for broadcasting text / image / PDF WhatsApp
| messages to recipient groups. Gated by the `whatsapp.send` permission
| (super-admin bypasses). Sending is also re-checked inside the controller.
*/

Route::middleware(['auth', 'role:super-admin,school-admin', 'permission:whatsapp.send'])
    ->prefix('admin/whatsapp')
    ->name('admin.whatsapp.')
    ->group(function () {
        Route::get('send', [WhatsappSendController::class, 'create'])->name('send');
        Route::post('send', [WhatsappSendController::class, 'store'])->name('send.store');
        Route::post('recipients/resolve', [WhatsappSendController::class, 'resolveRecipients'])->name('recipients.resolve');
        Route::get('recipients/search', [WhatsappSendController::class, 'searchUsers'])->name('recipients.search');
    });
