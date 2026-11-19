<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Hooks\ExamPointHooks;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PointsHooksExamTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*',
            '2026_05_25_130002_*', '2026_06_15_100000_*', '2026_11_19_371*'] as $pattern) {
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

    /** @return array{Exam, User, User, User} exam, teacher, student A, student B (2 questions, class of 2) */
    private function fixture(): array
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->roles()->attach(Role::where('slug', 'teacher')->first());
        $a = $this->createStudent($school);
        $b = User::factory()->create(['school_id' => $school->id]);
        $b->roles()->attach(Role::where('slug', 'student')->first());
        $class = ClassRoom::factory()->create();
        foreach ([$a, $b] as $s) {
            DB::table('class_student')->insert(['class_id' => $class->id, 'student_id' => $s->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        $exam = Exam::factory()->create(['academic_year_id' => \App\Models\AcademicYear::factory()->create()->id, 'teacher_id' => $teacher->id, 'class_id' => $class->id]);
        foreach ([1, 2] as $i) {
            DB::table('exam_questions')->insert(['exam_id' => $exam->id, 'question' => "q$i", 'type' => 'true_false', 'marks' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['student.correct_answers.subjects', 'teacher.exams'] as $rule) {
            PointRuleSetting::create(['school_id' => 0, 'rule_key' => $rule, 'points' => 10, 'is_enabled' => true]);
        }

        return [$exam, $teacher, $a, $b];
    }

    /** Submitted attempt with $correct correct answers (rows for questions 1..n). */
    private function attempt(Exam $exam, User $student, int $correct, bool $submitted = true): int
    {
        $n0 = DB::table('student_exams')->where('exam_id', $exam->id)->where('student_id', $student->id)->count() + 1;
        $id = DB::table('student_exams')->insertGetId(['attempt_number' => $n0, 'exam_id' => $exam->id, 'student_id' => $student->id,
            'submitted_at' => $submitted ? now() : null, 'status' => 'graded', 'created_at' => now(), 'updated_at' => now()]);
        $qs = DB::table('exam_questions')->where('exam_id', $exam->id)->pluck('id');
        foreach ($qs as $n => $qid) {
            DB::table('student_answers')->insert(['student_exam_id' => $id, 'question_id' => $qid,
                'is_correct' => $n < $correct, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $id;
    }

    public function test_best_attempt_awards_student_and_teacher_interaction(): void
    {
        [$exam, $teacher, $a] = $this->fixture();
        $hooks = app(ExamPointHooks::class);

        $this->attempt($exam, $a, 1);
        $hooks->examAttemptChanged($exam, $a->id);
        $this->assertSame(10, $this->balance($a));
        $this->assertSame(5, $this->balance($teacher)); // 1 of 2 targeted submitted

        $this->attempt($exam, $a, 2); // better 2nd attempt: best counts, no double award
        $hooks->examAttemptChanged($exam, $a->id);
        $this->assertSame(20, $this->balance($a));
        $this->assertSame(5, $this->balance($teacher));
    }

    public function test_reopened_attempt_is_excluded_until_resubmitted(): void
    {
        [$exam, $teacher, $a] = $this->fixture();
        $hooks = app(ExamPointHooks::class);
        $id = $this->attempt($exam, $a, 2);
        $hooks->examAttemptChanged($exam, $a->id);
        $this->assertSame(20, $this->balance($a));

        DB::table('student_exams')->where('id', $id)->update(['submitted_at' => null]);
        $hooks->examAttemptChanged($exam, $a->id);
        $this->assertSame(0, $this->balance($a));
        $this->assertSame(0, $this->balance($teacher));
    }

    public function test_correct_basis_uses_average_best_over_questions(): void
    {
        [$exam, $teacher, $a, $b] = $this->fixture();
        PointRuleSetting::where('rule_key', 'teacher.exams')->update(['options' => ['basis' => 'correct']]);
        $this->attempt($exam, $a, 2);
        $this->attempt($exam, $b, 1);
        app(ExamPointHooks::class)->examAttemptChanged($exam, $a->id);
        $this->assertSame(8, $this->balance($teacher)); // avg 1.5 / 2 = 0.75 * 10 = 7.5 -> 8
    }

    public function test_delete_revokes_everything(): void
    {
        [$exam, $teacher, $a] = $this->fixture();
        $this->attempt($exam, $a, 2);
        app(ExamPointHooks::class)->examAttemptChanged($exam, $a->id);
        $this->assertGreaterThan(0, $this->balance($a));

        $exam->delete();
        app(ExamPointHooks::class)->examDeleted($exam->id);
        $this->assertSame(0, $this->balance($a));
        $this->assertSame(0, $this->balance($teacher));
    }

    public function test_targeted_students_union_class_room_id_and_pivot_once_and_ignore_outsiders(): void
    {
        [$exam, $teacher, $a, $b] = $this->fixture();
        $school = School::find($a->school_id);
        // c: linked only through users.class_room_id; a: linked both ways (counted once)
        $c = User::factory()->create(['school_id' => $school->id, 'class_room_id' => $exam->class_id]);
        $c->roles()->attach(Role::where('slug', 'student')->first());
        $a->forceFill(['class_room_id' => $exam->class_id])->save();
        $outsider = User::factory()->create(['school_id' => $school->id]);
        $outsider->roles()->attach(Role::where('slug', 'student')->first());

        $this->attempt($exam, $c, 1);
        $this->attempt($exam, $outsider, 2);
        app(ExamPointHooks::class)->examAttemptChanged($exam, $c->id);

        $this->assertSame(3, $this->balance($teacher)); // 1 targeted submitter of 3 targeted = 3.33 -> 3
    }

    public function test_a_failing_hook_never_breaks_the_host_transaction(): void
    {
        [$exam, , $a] = $this->fixture();
        $this->attempt($exam, $a, 2);
        $this->mock(\App\Modules\Gamification\Support\RuleSettings::class)
            ->shouldReceive('effective')->andThrow(new \RuntimeException('points down'));

        DB::transaction(function () use ($exam, $a) {
            app(ExamPointHooks::class)->examAttemptChanged($exam, $a->id);
            DB::table('exams')->where('id', $exam->id)->update(['title' => 'host write kept']);
        });

        $this->assertSame('host write kept', DB::table('exams')->where('id', $exam->id)->value('title'));
    }

    public function test_hook_inside_a_transaction_awards_after_commit_and_nothing_on_rollback(): void
    {
        [$exam, , $a] = $this->fixture();
        $this->attempt($exam, $a, 2);

        try {
            DB::transaction(function () use ($exam, $a) {
                app(ExamPointHooks::class)->examAttemptChanged($exam, $a->id);
                throw new \RuntimeException('host failed');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, $this->balance($a));

        DB::transaction(function () use ($exam, $a) {
            app(ExamPointHooks::class)->examAttemptChanged($exam, $a->id);
            $this->assertSame(0, $this->balance($a)); // not before the host commits
        });
        $this->assertSame(20, $this->balance($a));
    }
}
