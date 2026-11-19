<?php

namespace Tests\Feature;

use App\Models\JobTitle;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Models\PointRewardPeriod;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Models\PointTransaction;
use Tests\TestCase;

class MyPointsAccessTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_04_150000_*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function tx(User $u, int $points, string $reason = null): void
    {
        PointTransaction::create(['school_id' => $u->school_id, 'user_id' => $u->id, 'rule_key' => 'student.assignments',
            'source_type' => 'manual', 'source_id' => $points + random_int(1, 99999), 'revision' => 1, 'points' => $points,
            'kind' => 'award', 'reason' => $reason, 'created_at' => now()]);
    }

    private function otherStudent(School $school, string $email): User
    {
        $s = User::factory()->create(['email' => $email, 'school_id' => $school->id]);
        $s->roles()->attach(\App\Models\Role::where('slug', 'student')->first());

        return $s;
    }

    public function test_student_sees_only_own_balance_and_rewards(): void
    {
        $school = School::factory()->create();
        $me = $this->createStudent($school);
        $other = $this->otherStudent($school, 'o@test.com');
        $this->tx($me, 7, 'سبب-ظاهر');
        $this->tx($other, 99, 'سبب-غريب');
        $period = PointRewardPeriod::create(['school_id' => $school->id, 'name' => 'شهر-نوفمبر',
            'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'min_points' => 5]);
        PointReward::create(['school_id' => $school->id, 'user_id' => $me->id, 'period_id' => $period->id, 'points_at_award' => 7]);

        $this->actingAs($me)->get(route('my.points.index', ['child' => $other->id]))
            ->assertOk()->assertSee('سبب-ظاهر')->assertSee('شهر-نوفمبر')->assertDontSee('سبب-غريب');
    }

    public function test_parent_switcher_is_limited_to_children_and_foreign_child_is_404(): void
    {
        $school = School::factory()->create();
        $parent = $this->createParent($school);
        $mine = $this->createStudent($school);
        $foreign = $this->otherStudent($school, 'f@test.com');
        $parent->children()->attach($mine->id);
        $this->tx($mine, 5, 'نقطة-ابني');
        $this->tx($foreign, 5, 'نقطة-غريب');

        $this->actingAs($parent)->get(route('my.points.index'))->assertOk()->assertSee('نقطة-ابني');
        $this->actingAs($parent)->get(route('my.points.index', ['child' => $mine->id]))->assertOk();
        $this->actingAs($parent)->get(route('my.points.index', ['child' => $foreign->id]))->assertNotFound();
    }

    public function test_vice_sees_his_rules_with_effective_options(): void
    {
        $school = School::factory()->create();
        $vice = $this->createSchoolAdmin($school);
        $vice->update(['job_title_id' => JobTitle::where('slug', 'vice')->firstOrFail()->id]);
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'vice.school_schedule_on_time', 'points' => 4,
            'is_enabled' => true, 'options' => ['deadline' => '2026-12-01 10:00']]);
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'vice.class_visits', 'points' => 2,
            'is_enabled' => true, 'options' => ['visits_per_term' => 6]]);

        $this->actingAs($vice)->get(route('my.points.index'))->assertOk()
            ->assertSee('2026-12-01 10:00')->assertSee(__('points.rules.vice.class_visits'))->assertSee(__('points_my.current_term'))
            ->assertDontSee(__('points.rules.vice.job_performance_on_time'));

        $this->actingAs($this->createTeacher($school))->get(route('my.points.index'))->assertOk()
            ->assertDontSee(__('points_my.vice_rules'));
    }
}
