<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\Schedule;
use App\Models\User;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Support\RuleSettings;
use Illuminate\Support\Carbon;

/** Points for the vice principal adding the school schedule on time; deleting the schedule takes it back. */
final class SchedulePointHooks
{
    public function __construct(private SyncActivityPoints $sync, private RevokeSourcePoints $revoke, private RuleSettings $settings) {}

    /** The beneficiary check (وكيل only) lives in SyncActivityPoints. */
    public function scheduleCreated(Schedule $schedule, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        $deadline = $this->settings->effective('vice.school_schedule_on_time', (int) $actor->school_id)['options']['deadline'] ?? null;
        $onTime = ! $deadline || ($schedule->created_at ?? now())->lte(Carbon::parse($deadline));

        $this->sync->execute(new ActivityPoints('vice.school_schedule_on_time', $actor->id, 'schedule', $schedule->id, $onTime ? 1.0 : 0.0, $actor->id));
    }

    /** Call AFTER the delete succeeded. */
    public function scheduleDeleted(int $scheduleId): void
    {
        $this->revoke->execute('schedule', $scheduleId, 'حذف الجدول');
    }
}
