<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Hooks\SchedulePointHooks;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Tests\TestCase;

class PointsHooksScheduleTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function balance(User $u): int
    {
        return app(PointLedgerRepository::class)->balance($u->id);
    }

    private function vice(string $slug = 'vice'): array
    {
        $school = School::factory()->create();
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'vice.school_schedule_on_time', 'points' => 7, 'is_enabled' => true]);
        $title = JobTitle::where('slug', $slug)->firstOrFail();
        $user = User::factory()->create(['school_id' => $school->id, 'job_title_id' => $title->id]);
        $user->roles()->attach(Role::where('slug', 'school-admin')->first());
        $class = ClassRoom::factory()->create();
        $schedule = Schedule::create(['class_id' => $class->id, 'academic_year_id' => $class->academic_year_id, 'semester' => 'first']);

        return [$user, $schedule];
    }

    public function test_award_idempotent_and_revoked_on_delete(): void
    {
        [$vice, $schedule] = $this->vice();
        $hooks = app(SchedulePointHooks::class);

        $hooks->scheduleCreated($schedule, $vice);
        $hooks->scheduleCreated($schedule, $vice);
        $this->assertSame(7, $this->balance($vice));

        $hooks->scheduleDeleted($schedule->id);
        $this->assertSame(0, $this->balance($vice));
    }

    public function test_after_deadline_earns_nothing(): void
    {
        [$vice, $schedule] = $this->vice();
        PointRuleSetting::where('rule_key', 'vice.school_schedule_on_time')->update(['options' => ['deadline' => now()->subDay()->toDateTimeString()]]);

        app(SchedulePointHooks::class)->scheduleCreated($schedule, $vice);
        $this->assertSame(0, $this->balance($vice));
    }

    public function test_before_deadline_earns_and_non_vice_does_not(): void
    {
        [$vice, $schedule] = $this->vice();
        PointRuleSetting::where('rule_key', 'vice.school_schedule_on_time')->update(['options' => ['deadline' => now()->addDay()->toDateTimeString()]]);
        $hooks = app(SchedulePointHooks::class);

        $hooks->scheduleCreated($schedule, $vice);
        $this->assertSame(7, $this->balance($vice));

        $other = User::factory()->create(['school_id' => $vice->school_id, 'email' => 'x@t.com']);
        $other->roles()->attach(Role::where('slug', 'school-admin')->first());
        $hooks->scheduleCreated($schedule, $other);
        $this->assertSame(0, $this->balance($other));
    }
}
