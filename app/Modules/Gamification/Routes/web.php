<?php

use App\Modules\Gamification\Controllers\ManagePointsController;
use App\Modules\Gamification\Controllers\MyPointsController;
use App\Modules\Gamification\Controllers\PointRulesController;
use App\Modules\Gamification\Controllers\RewardsController;
use App\Modules\Gamification\Models\PointTransaction;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('my/points', [MyPointsController::class, 'index'])->name('my.points.index');

    $staff = 'can:viewStaff,'.PointTransaction::class;
    Route::middleware($staff)->prefix('manage/points')->name('manage.points.')->group(function () {
        Route::get('/', [ManagePointsController::class, 'index'])->name('index');
        Route::get('users/{user}', [ManagePointsController::class, 'show'])->whereNumber('user')->name('show');
        Route::post('users/{user}/adjust', [ManagePointsController::class, 'adjust'])->whereNumber('user')
            ->middleware('can:adjust,'.PointTransaction::class)->name('adjust');

        Route::get('rules', [PointRulesController::class, 'index'])->middleware('can:manageRules,'.PointTransaction::class)->name('rules');
        Route::put('rules/platform', [PointRulesController::class, 'updatePlatform'])
            ->middleware('can:editPlatformRules,'.PointTransaction::class)->name('rules.platform');
        // per-rule check (editRuleOverride) happens in the controller
        Route::put('rules/school', [PointRulesController::class, 'updateSchool'])
            ->middleware('can:manageRules,'.PointTransaction::class)->name('rules.school');

        Route::middleware('can:manageRewards,'.PointTransaction::class)->group(function () {
            Route::get('periods', [RewardsController::class, 'periodsIndex'])->name('periods.index');
            Route::post('periods', [RewardsController::class, 'periodsStore'])->name('periods.store');
            Route::put('periods/{period}', [RewardsController::class, 'periodsUpdate'])->whereNumber('period')->name('periods.update');
            Route::delete('periods/{period}', [RewardsController::class, 'periodsDestroy'])->whereNumber('period')->name('periods.destroy');
            Route::get('rewards', [RewardsController::class, 'rewardsIndex'])->name('rewards.index');
            Route::patch('rewards/{reward}/deliver', [RewardsController::class, 'deliver'])->whereNumber('reward')->name('rewards.deliver');
        });
        Route::get('honour', [RewardsController::class, 'honour'])->name('honour');
    });
});
