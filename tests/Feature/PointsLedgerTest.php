<?php

namespace Tests\Feature;

use App\Models\JobTitle;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Models\PointTransaction;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\Gamification\Repositories\EloquentPointLedgerRepository;
use App\Modules\Gamification\Support\RuleCatalog;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PointsLedgerTest extends TestCase
{
    private const RULE = 'student.correct_answers.subjects';

    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        // The full chain includes unrelated MySQL-only ALTER statements.
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

    private function platform(string $rule, array $attrs = []): PointRuleSetting
    {
        return PointRuleSetting::create($attrs + ['school_id' => 0, 'rule_key' => $rule,
            'points' => 2, 'is_enabled' => true]);
    }

    private function sync(string $rule, User $user, float $units, string $type = 'exam', int $id = 1): void
    {
        app(SyncActivityPoints::class)->execute(new ActivityPoints($rule, $user->id, $type, $id, $units));
    }

    private function balance(User $user): int
    {
        return app(PointLedgerRepository::class)->balance($user->id);
    }

    private function teacher(School $school): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->roles()->attach(Role::where('slug', 'teacher')->first());

        return $user;
    }

    public function test_catalog_has_the_twenty_rules_and_no_seeded_settings(): void
    {
        $this->assertCount(20, RuleCatalog::RULES);
        $this->assertSame([8, 9, 3], array_map(fn ($b) => count(RuleCatalog::forBeneficiary($b)), ['student', 'teacher', 'vice']));
        $this->assertSame(0, PointRuleSetting::count());
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_first_award_and_idempotent_second_call(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);

        $this->sync(self::RULE, $student, 3);
        $this->sync(self::RULE, $student, 3);

        $this->assertSame(1, PointTransaction::count());
        $row = PointTransaction::first();
        $this->assertSame(6, $row->points);
        $this->assertSame('award', $row->kind);
        $this->assertSame(0, $row->revision);
        $this->assertSame($student->school_id, $row->school_id);
        $this->assertSame(2, $row->meta['unit_points']);
        $this->assertSame(6, $this->balance($student));
    }

    public function test_changed_units_write_one_delta_and_zero_reverses(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);

        $this->sync(self::RULE, $student, 3);
        $this->sync(self::RULE, $student, 5);
        $this->assertSame(10, $this->balance($student));
        $this->assertSame(['award', 'adjust'], PointTransaction::orderBy('id')->pluck('kind')->all());
        $this->assertSame(4, PointTransaction::orderBy('id')->get()[1]->points);

        $this->sync(self::RULE, $student, 0);
        $this->assertSame(0, $this->balance($student));
        $last = PointTransaction::orderByDesc('id')->first();
        $this->assertSame(['reverse', -10, 2], [$last->kind, $last->points, $last->revision]);
    }

    public function test_disabled_or_zero_writes_nothing_and_never_backfills(): void
    {
        $student = $this->createStudent();

        $this->sync(self::RULE, $student, 3); // no settings row at all
        $this->platform(self::RULE, ['is_enabled' => false]);
        $this->sync(self::RULE, $student, 3);
        $this->assertSame(0, PointTransaction::count());

        PointRuleSetting::where('rule_key', self::RULE)->update(['is_enabled' => true, 'points' => 0]);
        $this->sync(self::RULE, $student, 3);
        $this->assertSame(0, PointTransaction::count());

        // Enabling later does not backfill; the key is only written when its source fires again.
        PointRuleSetting::where('rule_key', self::RULE)->update(['points' => 2]);
        $this->assertSame(0, PointTransaction::count());
        $this->sync(self::RULE, $student, 3);
        $this->assertSame(6, $this->balance($student));
    }

    public function test_later_rule_change_does_not_reprice_but_disable_still_reverses(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);
        $this->sync(self::RULE, $student, 3);

        PointRuleSetting::where('rule_key', self::RULE)->update(['points' => 10]);
        $this->sync(self::RULE, $student, 4);
        $this->assertSame(8, $this->balance($student)); // 4 x the first row's 2, not 4 x 10

        PointRuleSetting::where('rule_key', self::RULE)->update(['is_enabled' => false]);
        $this->sync(self::RULE, $student, 0);
        $this->assertSame(0, $this->balance($student));
    }

    public function test_school_override_only_when_platform_allows_and_can_only_narrow(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);
        PointRuleSetting::create(['school_id' => $student->school_id, 'rule_key' => self::RULE, 'points' => 9]);
        $this->sync(self::RULE, $student, 1, 'exam', 1);
        $this->assertSame(2, $this->balance($student)); // override ignored: not allowed

        PointRuleSetting::where('school_id', 0)->update(['allow_school_override' => true]);
        $this->sync(self::RULE, $student, 1, 'exam', 2);
        $this->assertSame(11, $this->balance($student)); // +9 from the override

        PointRuleSetting::where('school_id', $student->school_id)->update(['is_enabled' => false]);
        $this->sync(self::RULE, $student, 1, 'exam', 3);
        $this->assertSame(11, $this->balance($student)); // school switched it off

        PointRuleSetting::where('school_id', $student->school_id)->update(['is_enabled' => true]);
        PointRuleSetting::where('school_id', 0)->update(['is_enabled' => false]);
        $this->sync(self::RULE, $student, 1, 'exam', 4);
        $this->assertSame(11, $this->balance($student)); // platform master switch beats the school
    }

    public function test_beneficiary_must_match_the_rule(): void
    {
        $school = School::factory()->create();
        $student = $this->createStudent($school);
        $teacher = $this->teacher($school);
        $this->platform(self::RULE);
        $this->platform('teacher.exams');
        $this->platform('vice.class_visits');

        $this->sync(self::RULE, $teacher, 1);
        $this->sync('teacher.exams', $student, 1);
        $this->sync('vice.class_visits', $teacher, 1, 'class_visit');
        $this->assertSame(0, PointTransaction::count());

        $vice = User::factory()->create(['school_id' => $school->id,
            'job_title_id' => JobTitle::where('slug', 'vice')->firstOrFail()->id]);
        $vice->roles()->attach(Role::where('slug', 'school-admin')->first());
        $this->sync('vice.class_visits', $vice, 1, 'class_visit');
        $this->assertSame(2, $this->balance($vice));
        $this->sync('teacher.exams', $teacher, 1);
        $this->assertSame(2, $this->balance($teacher));
    }

    public function test_unknown_rule_is_ignored(): void
    {
        $student = $this->createStudent();
        $this->sync('nope.rule', $student, 1);
        $this->assertSame(0, PointTransaction::count());
    }

    public function test_revoke_source_zeroes_every_key_of_that_source(): void
    {
        $school = School::factory()->create();
        $student = $this->createStudent($school);
        $teacher = $this->teacher($school);
        $this->platform(self::RULE);
        $this->platform('teacher.exams');
        $this->sync(self::RULE, $student, 3, 'exam', 7);
        $this->sync('teacher.exams', $teacher, 1, 'exam', 7);
        $this->sync(self::RULE, $student, 3, 'exam', 8);

        app(RevokeSourcePoints::class)->execute('exam', 7, 'حذف الاختبار');

        $this->assertSame(6, $this->balance($student)); // exam 8 untouched
        $this->assertSame(0, $this->balance($teacher));
        $this->assertSame('حذف الاختبار', PointTransaction::where('kind', 'reverse')->first()->reason);
        app(RevokeSourcePoints::class)->execute('exam', 7); // already at 0: no new rows
        $this->assertSame(5, PointTransaction::count());
    }

    public function test_same_revision_race_is_retried_and_converges(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);
        $this->sync(self::RULE, $student, 3); // real row: 6 points, revision 0

        // First read is stale (empty) like a concurrent first award: the insert collides, the retry re-reads.
        $this->app->instance(PointLedgerRepository::class, new class extends EloquentPointLedgerRepository {
            private bool $stale = true;

            public function keyRowsForUpdate(int $userId, string $ruleKey, string $sourceType, int $sourceId): Collection
            {
                if ($this->stale) {
                    $this->stale = false;

                    return new Collection;
                }

                return parent::keyRowsForUpdate($userId, $ruleKey, $sourceType, $sourceId);
            }
        });
        $this->sync(self::RULE, $student, 5);

        $this->assertSame(10, PointTransaction::where('user_id', $student->id)->sum('points'));
        $this->assertSame(2, PointTransaction::count());
    }

    public function test_failure_inside_the_award_never_reaches_the_host(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);
        $this->app->instance(PointLedgerRepository::class, new class extends EloquentPointLedgerRepository {
            public function keyRowsForUpdate(int $userId, string $ruleKey, string $sourceType, int $sourceId): Collection
            {
                throw new \RuntimeException('ledger down');
            }
        });

        $this->sync(self::RULE, $student, 3);

        $this->assertSame(0, PointTransaction::count());
    }

    public function test_award_waits_for_the_surrounding_transaction_to_commit(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);

        \DB::transaction(function () use ($student) {
            $this->sync(self::RULE, $student, 3);
            $this->assertSame(0, PointTransaction::count());
        });

        $this->assertSame(1, PointTransaction::count());
    }

    public function test_ledger_rows_are_immutable(): void
    {
        $student = $this->createStudent();
        $this->platform(self::RULE);
        $this->sync(self::RULE, $student, 3);

        $this->expectException(\LogicException::class);
        PointTransaction::first()->update(['points' => 99]);
    }
}
