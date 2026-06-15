<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController;

// School Calendar — print (PDF: daily / weekly / monthly). Gated on calendar.print
// inside the controller. Staff-only route group (mirrors the manage CRUD group).
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/school-calendar')
    ->name('manage.school-calendar.')
    ->group(function () {
        Route::get('print', [ManageSchoolCalendarController::class, 'print'])->name('print');
    });
