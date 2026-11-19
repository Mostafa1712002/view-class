<?php

namespace Tests\Feature;

use App\Models\WeeklyPlan;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Hooks\PreparationPointHooks;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Tests\TestCase;

class PointsHooksPreparationTest extends TestCase
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

    public function test_plan_awards_once_and_delete_reverses(): void
    {
        $school = School::factory()->create();
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'teacher.preparation', 'points' => 3, 'is_enabled' => true]);
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->roles()->attach(Role::where('slug', 'teacher')->first());
        $plan = WeeklyPlan::create(['teacher_id' => $teacher->id, 'subject_id' => \App\Models\Subject::factory()->create()->id,
            'class_id' => \App\Models\ClassRoom::factory()->create()->id, 'week_start_date' => '2026-11-15', 'week_end_date' => '2026-11-21']);
        $hooks = app(PreparationPointHooks::class);

        $hooks->planSaved($plan);
        $hooks->planSaved($plan);   // idempotent
        $this->assertSame(3, $this->balance($teacher));

        $hooks->planDeleted($plan->id);
        $this->assertSame(0, $this->balance($teacher));
    }
}
