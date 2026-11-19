<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\User;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Models\PointRewardPeriod;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\Gamification\Repositories\Contracts\RewardRepository;
use Carbon\Carbon;
use Tests\TestCase;

class PointsRewardsTest extends TestCase
{
    private const RULE = 'student.correct_answers.subjects';

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

    private function setUpRule(array $attrs = []): void
    {
        PointRuleSetting::create($attrs + ['school_id' => 0, 'rule_key' => self::RULE, 'points' => 2, 'is_enabled' => true]);
    }

    private function period(User $student, array $attrs = []): PointRewardPeriod
    {
        return PointRewardPeriod::create($attrs + ['school_id' => $student->school_id, 'name' => 'شهر',
            'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'min_points' => 10]);
    }

    private function award(User $student, float $units, int $sourceId, string $at = '2026-11-10 10:00:00'): void
    {
        app(SyncActivityPoints::class)->execute(new ActivityPoints(
            self::RULE, $student->id, 'exam', $sourceId, $units, occurredAt: Carbon::parse($at)));
    }

    public function test_period_points_are_used_inside_an_active_period(): void
    {
        $student = $this->createStudent();
        $this->setUpRule(['period_points' => 5]);
        $period = $this->period($student, ['min_points' => 1000]);

        $this->award($student, 2, 1);                       // inside: 2 x 5
        $this->award($student, 2, 2, '2026-12-05 10:00:00'); // outside: 2 x 2
        $balance = app(PointLedgerRepository::class)->balance($student->id);

        $this->assertSame(14, $balance);
        $this->assertSame($period->id, \App\Modules\Gamification\Models\PointTransaction::orderBy('id')->first()->meta['period_id']);
    }

    public function test_threshold_creates_exactly_one_reward(): void
    {
        $student = $this->createStudent();
        $this->setUpRule();
        $period = $this->period($student); // min 10

        $this->award($student, 4, 1); // 8 points
        $this->assertSame(0, PointReward::count());
        $this->award($student, 1, 2); // 10 -> reward
        $this->award($student, 3, 3); // 16 -> no duplicate

        $this->assertSame(1, PointReward::count());
        $reward = PointReward::first();
        $this->assertSame([$period->id, $student->id, 'earned', 10],
            [$reward->period_id, $reward->user_id, $reward->status, $reward->points_at_award]);
    }

    public function test_last_day_of_the_period_counts(): void
    {
        $student = $this->createStudent();
        $this->setUpRule();
        $period = $this->period($student, ['min_points' => 6]);

        $this->award($student, 3, 1, '2026-11-30 23:30:00');

        $this->assertSame(6, app(RewardRepository::class)->periodSum($student->id, $period));
        $this->assertSame(1, PointReward::count());

        $this->award($student, 5, 2, '2026-12-01 00:10:00'); // next day: outside
        $this->assertSame(6, app(RewardRepository::class)->periodSum($student->id, $period));
    }

    public function test_grade_scoped_period_only_rewards_that_grade(): void
    {
        $student = $this->createStudent();
        $this->setUpRule();
        $other = Section::create(['name' => 'Other grade', 'school_id' => $student->school_id]);
        $this->period($student, ['section_id' => $other->id, 'min_points' => 2]);

        $this->award($student, 5, 1);

        $this->assertSame(0, PointReward::count());
    }

    public function test_deliver_stamps_who_and_when(): void
    {
        $student = $this->createStudent();
        $admin = User::factory()->create(['school_id' => $student->school_id]);
        $this->setUpRule();
        $this->period($student, ['min_points' => 2]);
        $this->award($student, 1, 1);

        $reward = app(RewardRepository::class)->deliver(PointReward::first(), $admin->id);

        $this->assertSame('delivered', $reward->status);
        $this->assertSame($admin->id, $reward->delivered_by);
        $this->assertNotNull($reward->delivered_at);
    }

    public function test_honour_list_ranks_positive_totals_of_the_month(): void
    {
        $school = \App\Models\School::factory()->create();
        $a = $this->createStudent($school);
        $b = User::factory()->create(['school_id' => $school->id]);
        $b->roles()->attach(\App\Models\Role::where('slug', 'student')->first());
        $this->setUpRule();

        $this->award($a, 2, 1);
        $this->award($b, 5, 2);
        $this->award($a, 1, 3, '2026-10-20 10:00:00'); // other month

        $list = app(RewardRepository::class)->honourList($school->id, '2026-11', 'student');

        $this->assertSame([$b->id, $a->id], $list->pluck('user_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame([10, 4], $list->pluck('points')->map(fn ($v) => (int) $v)->all());
    }
}
