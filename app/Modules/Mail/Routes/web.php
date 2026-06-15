<?php

use App\Modules\Mail\Controllers\MailboxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Mailbox — module routes
|--------------------------------------------------------------------------
|
| These complement the base mailbox routes registered in routes/web.php under
| the `my.mailbox.*` name + `my/mailbox` prefix. They add draft editing and
| reply / forward composing (Trello #236 acceptance criteria).
|
| To activate, add this require inside routes/web.php (do NOT duplicate the
| middleware/prefix/name — these definitions already declare them):
|
|     require base_path('app/Modules/Mail/Routes/web.php');
|
| Place the require AFTER the existing `// Internal Mailbox` group so the route
| names resolve in the same `my.mailbox.` namespace.
*/

Route::middleware(['auth'])->prefix('my/mailbox')->name('my.mailbox.')->group(function () {
    // Draft editing
    Route::get('/{mail}/edit', [MailboxController::class, 'edit'])->whereNumber('mail')->name('edit');
    Route::put('/{mail}', [MailboxController::class, 'update'])->whereNumber('mail')->name('update');

    // Reply / Forward (open the compose form prefilled)
    Route::get('/{mail}/reply', [MailboxController::class, 'reply'])->whereNumber('mail')->name('reply');
    Route::get('/{mail}/forward', [MailboxController::class, 'forward'])->whereNumber('mail')->name('forward');
});
