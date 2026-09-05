<?php

use App\Modules\Portfolio\Controllers\AdminPortfolioController;
use App\Modules\Portfolio\Controllers\TeacherPortfolioController;
use Illuminate\Support\Facades\Route;

/*
| Achievement portfolio ("إدارة ملف أنجاز") routes.
| Required from routes/web.php.
*/

Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/portfolio')
    ->name('admin.portfolio.')
    ->group(function () {
        Route::get('/', [AdminPortfolioController::class, 'index'])->name('index');
        Route::get('/{user}', [AdminPortfolioController::class, 'showUser'])->whereNumber('user')->name('show');
        Route::post('/{user}/entries', [AdminPortfolioController::class, 'storeEntry'])->whereNumber('user')->name('entries.store');
        Route::put('/{user}/entries/{entry}', [AdminPortfolioController::class, 'updateEntry'])->whereNumber('user')->whereNumber('entry')->name('entries.update');
        Route::delete('/{user}/entries/{entry}', [AdminPortfolioController::class, 'destroyEntry'])->whereNumber('user')->whereNumber('entry')->name('entries.destroy');
    });

Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('teacher/portfolio')
    ->name('teacher.')
    ->group(function () {
        // Route name kept as the bare `teacher.portfolio` (mirrors `student.portfolio`).
        Route::get('/', [TeacherPortfolioController::class, 'portfolio'])->name('portfolio');
        Route::post('/entries', [TeacherPortfolioController::class, 'storeEntry'])->name('portfolio.entries.store');
        Route::put('/entries/{entry}', [TeacherPortfolioController::class, 'updateEntry'])->whereNumber('entry')->name('portfolio.entries.update');
        Route::delete('/entries/{entry}', [TeacherPortfolioController::class, 'destroyEntry'])->whereNumber('entry')->name('portfolio.entries.destroy');
    });
