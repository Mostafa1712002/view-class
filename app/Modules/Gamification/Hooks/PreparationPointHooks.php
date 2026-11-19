<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\WeeklyPlan;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;

/** Points for teacher preparation: one event per weekly plan; deleting the plan takes it back. */
final class PreparationPointHooks
{
    public function __construct(private SyncActivityPoints $sync, private RevokeSourcePoints $revoke) {}

    public function planSaved(WeeklyPlan $plan): void
    {
        $this->sync->execute(new ActivityPoints('teacher.preparation', (int) $plan->teacher_id, 'weekly_plan', $plan->id, 1.0, auth()->id()));
    }

    /** Call AFTER the delete succeeded. */
    public function planDeleted(int $planId): void
    {
        $this->revoke->execute('weekly_plan', $planId, 'حذف التحضير');
    }
}
