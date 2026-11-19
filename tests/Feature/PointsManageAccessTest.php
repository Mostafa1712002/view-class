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

class PointsManageAccessTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_04_150000_*',
            '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        // roles are seeded after the migrations, so re-run the grant
        (require database_path('migrations/2026_11_19_371001_seed_points_permissions.php'))->up();
    }

    private function row(User $u, int $points = 5): void
    {
        PointTransaction::create(['school_id' => $u->school_id, 'user_id' => $u->id, 'rule_key' => 'manual', 'source_type' => 'manual',
            'source_id' => 0, 'revision' => 0, 'points' => $points, 'kind' => 'manual', 'reason' => 'x', 'created_at' => now()]);
    }

    public function test_every_staff_page_renders_for_super_admin_and_school_admin(): void
    {
        $school = School::factory()->create();
        $student = $this->createStudent($school);
        $this->row($student);
        $period = PointRewardPeriod::create(['school_id' => $school->id, 'name' => 'P1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'min_points' => 3]);
        PointReward::create(['school_id' => $school->id, 'user_id' => $student->id, 'period_id' => $period->id, 'points_at_award' => 5, 'status' => 'earned']);
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'teacher.exams', 'points' => 2, 'is_enabled' => true, 'allow_school_override' => true]);

        foreach ([$this->createSuperAdmin(), $this->createSchoolAdmin($school)] as $admin) {
            $this->actingAs($admin);
            foreach (['manage.points.index', 'manage.points.rules', 'manage.points.periods.index', 'manage.points.rewards.index', 'manage.points.honour'] as $name) {
                $this->get(route($name))->assertOk();
            }
            $this->get(route('manage.points.show', $student->id))->assertOk()->assertSee('+5');
            foreach (['teacher', 'vice'] as $type) {
                $this->get(route('manage.points.index', ['type' => $type]))->assertOk();
            }
        }
    }

    public function test_school_admin_is_scoped_to_own_school(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $outsider = $this->createStudent($b);
        $this->actingAs($this->createSchoolAdmin($a));
        $this->get(route('manage.points.show', $outsider->id))->assertNotFound();
        $this->post(route('manage.points.adjust', $outsider->id), ['points' => 5, 'reason' => 'x'])->assertNotFound();
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_filter_ids_from_another_school_are_ignored(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $foreignSection = \App\Models\Section::factory()->create(['school_id' => $b->id]);
        $foreignClass = \App\Models\ClassRoom::factory()->create(['section_id' => $foreignSection->id, 'name' => 'ForeignClassXYZ']);
        $mine = $this->createStudent($a);
        $this->row($mine);
        $this->actingAs($this->createSchoolAdmin($a));

        $this->get(route('manage.points.index', ['section_id' => $foreignSection->id]))
            ->assertOk()->assertDontSee('ForeignClassXYZ')->assertSee($mine->name);
        $this->get(route('manage.points.index', ['class_id' => $foreignClass->id]))
            ->assertOk()->assertDontSee('ForeignClassXYZ')->assertSee($mine->name);
    }

    public function test_adjust_needs_reason_and_is_blocked_on_self(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $student = $this->createStudent($school);
        $this->actingAs($admin);
        $this->post(route('manage.points.adjust', $student->id), ['points' => 5])->assertSessionHasErrors('reason');
        $this->post(route('manage.points.adjust', $student->id), ['points' => 0, 'reason' => 'x'])->assertSessionHasErrors('points');
        $this->post(route('manage.points.adjust', $student->id), ['points' => -4, 'reason' => 'خصم'])->assertRedirect();
        $this->assertSame(-4, (int) PointTransaction::where('user_id', $student->id)->sum('points'));
        $this->post(route('manage.points.adjust', $student->id), ['points' => 2, 'reason' => 'again'])->assertRedirect();
        $this->assertSame(2, PointTransaction::where('user_id', $student->id)->count());
        $this->post(route('manage.points.adjust', $admin->id), ['points' => 5, 'reason' => 'x'])->assertForbidden();
    }

    public function test_rules_school_admin_only_overrides_allowed_rules_and_not_vice_for_vice(): void
    {
        $school = School::factory()->create();
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'teacher.exams', 'points' => 2, 'is_enabled' => true, 'allow_school_override' => true]);
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'teacher.preparation', 'points' => 1, 'is_enabled' => true, 'allow_school_override' => false]);
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => 'vice.class_visits', 'points' => 1, 'is_enabled' => true, 'allow_school_override' => true]);

        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin)->put(route('manage.points.rules.platform'), [])->assertForbidden();
        $this->put(route('manage.points.rules.school'), ['rules' => [
            'teacher.exams' => ['points' => 5, 'options' => ['basis' => 'correct']],
            'teacher.preparation' => ['points' => 9],
        ]])->assertRedirect();
        $row = PointRuleSetting::where('school_id', $school->id)->get()->keyBy('rule_key');
        $this->assertSame(5, $row['teacher.exams']->points);
        $this->assertSame(['basis' => 'correct'], $row['teacher.exams']->options);
        $this->assertFalse($row->has('teacher.preparation'));

        $vice = User::factory()->create(['school_id' => $school->id, 'job_title_id' => JobTitle::where('slug', 'vice')->firstOrFail()->id]);
        $vice->roles()->attach(\App\Models\Role::where('slug', 'school-admin')->first());
        $this->actingAs($vice)->put(route('manage.points.rules.school'), ['rules' => ['vice.class_visits' => ['points' => 3]]])->assertForbidden();
    }

    public function test_super_admin_saves_platform_rules_and_unwired_are_ignored(): void
    {
        $this->actingAs($this->createSuperAdmin())->put(route('manage.points.rules.platform'), ['rules' => [
            'student.correct_answers.subjects' => ['points' => 2, 'is_enabled' => 1],
            'teacher.motivation' => ['points' => 7, 'is_enabled' => 1],
            'vice.class_visits' => ['points' => 1, 'options' => ['visits_per_term' => 4]],
        ]])->assertRedirect();
        $this->assertSame(2, PointRuleSetting::where('rule_key', 'student.correct_answers.subjects')->value('points'));
        $this->assertNull(PointRuleSetting::where('rule_key', 'teacher.motivation')->first());
        $this->assertSame(['visits_per_term' => 4], PointRuleSetting::where('rule_key', 'vice.class_visits')->first()->options);
        $this->put(route('manage.points.rules.platform'), ['rules' => ['teacher.exams' => ['points' => 'abc']]])->assertSessionHasErrors();
    }

    public function test_periods_crud_overlap_warning_and_deliver(): void
    {
        $school = School::factory()->create();
        $student = $this->createStudent($school);
        $this->actingAs($this->createSchoolAdmin($school));
        $this->post(route('manage.points.periods.store'), ['name' => 'A', 'starts_on' => '2026-01-01', 'ends_on' => '2026-01-31', 'min_points' => 10])
            ->assertRedirect()->assertSessionMissing('warning');
        $this->post(route('manage.points.periods.store'), ['name' => 'B', 'starts_on' => '2026-01-15', 'ends_on' => '2026-02-15', 'min_points' => 10])
            ->assertSessionHas('warning');
        $this->post(route('manage.points.periods.store'), ['name' => 'C', 'starts_on' => '2026-03-01', 'ends_on' => '2026-02-01', 'min_points' => 10])
            ->assertSessionHasErrors('ends_on');
        $a = PointRewardPeriod::where('name', 'A')->firstOrFail();
        $this->put(route('manage.points.periods.update', $a->id), ['name' => 'A2', 'starts_on' => '2026-01-01', 'ends_on' => '2026-01-10', 'min_points' => 5])->assertRedirect();
        $this->assertSame('A2', $a->fresh()->name);
        $this->delete(route('manage.points.periods.destroy', $a->id))->assertRedirect();
        $this->assertNull(PointRewardPeriod::find($a->id));

        $reward = PointReward::create(['school_id' => $school->id, 'user_id' => $student->id, 'period_id' => PointRewardPeriod::first()->id, 'points_at_award' => 10, 'status' => 'earned']);
        $this->patch(route('manage.points.rewards.deliver', $reward->id))->assertRedirect();
        $this->assertSame('delivered', $reward->fresh()->status);
        $this->assertNotNull($reward->fresh()->delivered_by);

        $other = PointReward::create(['school_id' => School::factory()->create()->id, 'user_id' => $student->id, 'period_id' => 1, 'points_at_award' => 1, 'status' => 'earned']);
        $this->patch(route('manage.points.rewards.deliver', $other->id))->assertNotFound();
    }
}
