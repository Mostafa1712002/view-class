<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ClassVisit;
use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Hooks\EvaluationPointHooks;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Tests\TestCase;

class PointsHooksEvaluationTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*',
            '2026_06_08_100001_*', '2026_06_08_100009_*', '2026_06_08_100010_*', '2026_06_08_120000_*', '2026_11_19_371*'] as $pattern) {
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

    private function vice(School $school): User
    {
        $u = User::factory()->create(['school_id' => $school->id, 'job_title_id' => JobTitle::where('slug', 'vice')->firstOrFail()->id]);
        $u->roles()->attach(Role::where('slug', 'school-admin')->first());

        return $u;
    }

    private function rule(string $key, array $options = []): void
    {
        PointRuleSetting::create(['school_id' => 0, 'rule_key' => $key, 'points' => 5, 'is_enabled' => true, 'options' => $options ?: null]);
    }

    private function evaluation(User $vice, string $domain, $close, array $extra = []): Evaluation
    {
        $form = EvaluationForm::create(['school_id' => $vice->school_id, 'title' => 'F', 'usage_domain' => $domain, 'close_date' => $close]);

        return Evaluation::create(array_merge(['form_id' => $form->id, 'evaluator_id' => $vice->id, 'subject_type' => 'user', 'subject_id' => $vice->id,
            'school_id' => $vice->school_id, 'status' => 'completed', 'submitted_at' => now()], $extra));
    }

    public function test_job_performance_on_time_reopen_and_late(): void
    {
        $school = School::factory()->create();
        $vice = $this->vice($school);
        $this->rule('vice.job_performance_on_time');
        $hooks = app(EvaluationPointHooks::class);

        $e = $this->evaluation($vice, 'job_performance', now()->addDay());
        $hooks->evaluationChanged($e);
        $hooks->evaluationChanged($e);
        $this->assertSame(5, $this->balance($vice));

        $e->update(['status' => 'draft', 'submitted_at' => null]);   // reopen
        $hooks->evaluationChanged($e->fresh());
        $this->assertSame(0, $this->balance($vice));

        $closingDay = $this->evaluation($vice, 'job_performance', now()->startOfDay());   // date-only close_date, submitted that day
        $hooks->evaluationChanged($closingDay);
        $this->assertSame(5, $this->balance($vice));

        $late = $this->evaluation($vice, 'job_performance', now()->subDay());
        $hooks->evaluationChanged($late);
        $this->assertSame(5, $this->balance($vice)); // late earns nothing

        $other = $this->evaluation($vice, 'teacher', now()->addDay());   // not a job-performance form
        $hooks->evaluationChanged($other);
        $this->assertSame(5, $this->balance($vice));
    }

    public function test_class_visits_cap_term_and_delete(): void
    {
        $school = School::factory()->create();
        $vice = $this->vice($school);
        $this->rule('vice.class_visits', ['visits_per_term' => 1]);
        $year = AcademicYear::create(['name' => 'Y', 'school_id' => $school->id, 'start_date' => now()->subMonths(6), 'end_date' => now()->addMonths(6), 'is_current' => true]);
        AcademicTerm::create(['academic_year_id' => $year->id, 'name' => 'T1', 'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addMonth()->toDateString(), 'is_current' => true, 'sort_order' => 1]);
        $hooks = app(EvaluationPointHooks::class);

        $make = function (string $date) use ($school, $vice) {
            $v = ClassVisit::create(['school_id' => $school->id, 'supervisor_id' => $vice->id, 'teacher_id' => $vice->id, 'visit_date' => $date, 'status' => 'completed']);
            $e = $this->evaluation($vice, 'class_visit', null, ['class_visit_id' => $v->id]);

            return [$v, $e];
        };

        [$v1, $e1] = $make(now()->toDateString());
        $hooks->evaluationChanged($e1);
        $this->assertSame(5, $this->balance($vice));

        [, $e2] = $make(now()->toDateString());          // cap of 1 reached
        $hooks->evaluationChanged($e2);
        $this->assertSame(5, $this->balance($vice));

        $hooks->evaluationChanged($e1);                  // first stays awarded
        $this->assertSame(5, $this->balance($vice));

        [, $e3] = $make(now()->addYear()->toDateString()); // outside the term
        $hooks->evaluationChanged($e3);
        $this->assertSame(5, $this->balance($vice));

        $hooks->classVisitDeleted($v1->id);
        $this->assertSame(0, $this->balance($vice));
    }

    public function test_job_performance_without_close_date_counts_as_on_time(): void
    {
        $school = School::factory()->create();
        $vice = $this->vice($school);
        $this->rule('vice.job_performance_on_time');

        app(EvaluationPointHooks::class)->evaluationChanged($this->evaluation($vice, 'job_performance', null));
        $this->assertSame(5, $this->balance($vice));
    }
}
