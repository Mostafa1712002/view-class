<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\School;
use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Attempts\AttemptException;
use App\Modules\SpecialPrograms\Actions\Attempts\FinishAttempt;
use App\Modules\SpecialPrograms\Actions\Attempts\LogExit;
use App\Modules\SpecialPrograms\Actions\Attempts\SaveAnswer;
use App\Modules\SpecialPrograms\Actions\Attempts\StartSubject;
use App\Modules\SpecialPrograms\Actions\Picking\NafisPicker;
use App\Modules\SpecialPrograms\Actions\Picking\QiyasPicker;
use App\Modules\SpecialPrograms\Actions\Picking\QuestionPicker;
use App\Modules\SpecialPrograms\Actions\Picking\TahsiliPicker;
use App\Modules\SpecialPrograms\Events\ProgramAttemptCompleted;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Repositories\Contracts\AttemptRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class AttemptLifecycleTest extends TestCase
{
    use LoadsSpMigrations;

    private School $school;

    private User $student;

    private bool $eligible = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
        Carbon::setTestNow('2026-11-20 10:00:00');
        $this->school = School::factory()->create();
        $this->student = User::factory()->create(['school_id' => $this->school->id]);

        $this->mock(EnrollmentRepository::class, fn ($m) => $m->shouldReceive('isEnrolled')->andReturnUsing(fn () => $this->eligible));
        $this->mock(ExamRepository::class, fn ($m) => $m->shouldReceive('isInAudience')->andReturnUsing(fn () => $this->eligible));
        $this->mock(QuestionPoolRepository::class, fn ($m) => $m->shouldReceive('snapshot')->andReturnUsing(function (array $ids) {
            $out = [];
            foreach ($ids as $id) {
                $out[$id] = ['bank_question_id' => 1000 + $id, 'stem' => "Q$id", 'image_path' => null,
                    'options' => [['text' => 'a', 'image' => null], ['text' => 'b', 'image' => null], ['text' => 'c', 'image' => null]],
                    'correct_index' => 0, 'sp_skill_id' => null, 'difficulty' => 'easy', 'passage_id' => null, 'sub_index' => 0];
            }

            return $out;
        }));
        $picker = new class implements QuestionPicker
        {
            public function pick(\App\Modules\SpecialPrograms\Models\SpExamSubject $subject, User $student): array
            {
                return range(1, $subject->total_questions);
            }
        };
        foreach ([QiyasPicker::class, NafisPicker::class, TahsiliPicker::class] as $c) {
            $this->app->instance($c, $picker);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return int[] exam_subject ids ordered */
    private function exam(string $program = 'qiyas', int $subjects = 1, array $over = []): array
    {
        $examId = DB::table('sp_exams')->insertGetId($over + ['school_id' => $this->school->id, 'program' => $program, 'title' => 'E',
            'type' => 'multi', 'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => 1, 'creator_role' => 'admin',
            'created_at' => now(), 'updated_at' => now()]);
        $subjIds = DB::table('sp_subjects')->where('program', $program)->orderBy('id')->pluck('id');
        $out = [];
        for ($i = 0; $i < $subjects; $i++) {
            $out[] = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $examId, 'sp_subject_id' => $subjIds[$i],
                'duration_minutes' => 30, 'total_questions' => 4, 'order_index' => $i + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $out;
    }

    private function start(int $esId): SpAttempt
    {
        return app(StartSubject::class)->execute($esId, $this->student);
    }

    private function answerAll(SpAttempt $a, int $correct): void
    {
        foreach ($a->items()->take($correct)->get() as $item) {
            app(SaveAnswer::class)->execute($a->id, $this->student, $item->id, 0);
        }
    }

    public function test_double_start_reuses_one_item_set_and_deadline_is_stable(): void
    {
        [$es] = $this->exam();
        $first = $this->start($es);
        Carbon::setTestNow(now()->addMinutes(10));
        $second = $this->start($es);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SpAttempt::count());
        $this->assertSame(4, DB::table('sp_attempt_items')->count());
        $this->assertTrue($first->deadline_at->equalTo($second->deadline_at));
        $this->assertSame(1800, (int) $first->started_at->diffInSeconds($first->deadline_at));
        $this->assertSame(4, $second->items_count);
    }

    public function test_late_answer_is_rejected_and_attempt_times_out(): void
    {
        [$es] = $this->exam();
        $a = $this->start($es);
        $item = $a->items()->first();
        Carbon::setTestNow(now()->addMinutes(30)->addSeconds(3)); // inside the 5 s grace
        app(SaveAnswer::class)->execute($a->id, $this->student, $item->id, 0);

        Carbon::setTestNow(now()->addSeconds(10));
        try {
            app(SaveAnswer::class)->execute($a->id, $this->student, $item->id, 1);
            $this->fail('late answer accepted');
        } catch (AttemptException $e) {
            $this->assertSame('late', $e->reason);
        }
        $this->assertSame('timed_out', $a->fresh()->status);
        $this->assertSame(0, (int) $item->fresh()->answer_index);
    }

    public function test_answer_response_never_reveals_correctness(): void
    {
        [$es] = $this->exam();
        $a = $this->start($es);
        $res = app(SaveAnswer::class)->execute($a->id, $this->student, $a->items()->first()->id, 2);

        $this->assertSame(['saved', 'remaining_seconds'], array_keys($res));
        $this->assertSame(1800, $res['remaining_seconds']);
        $this->assertFalse($a->items()->first()->is_correct);
    }

    public function test_finish_scores_unanswered_as_wrong_and_fires_event_once(): void
    {
        Event::fake([ProgramAttemptCompleted::class]);
        [$es] = $this->exam('tahsili');
        $a = $this->start($es);
        $this->answerAll($a, 1); // 1 of 4 answered, correct

        $done = app(FinishAttempt::class)->execute($a->id);
        app(FinishAttempt::class)->execute($a->id);
        app(FinishAttempt::class)->execute($a->id, 'auto_ended');

        $this->assertSame('completed', $done->status);
        $this->assertSame(1, $done->correct_count);
        $this->assertSame(1, $done->answered_count);
        $this->assertEquals(25.0, $done->score_pct);
        Event::assertDispatchedTimes(ProgramAttemptCompleted::class, 1);
        Event::assertDispatched(ProgramAttemptCompleted::class, fn ($e) => $e->attemptId === $a->id && $e->studentId === $this->student->id
            && $e->schoolId === $this->school->id && $e->program === 'tahsili' && $e->subjectCode !== '' && $e->correctCount === 1
            && $e->totalCount === 4 && $e->scorePct === 25.0 && $e->status === 'completed' && $e->examCompleted === true);
    }

    public function test_exam_completed_only_on_last_subject(): void
    {
        Event::fake([ProgramAttemptCompleted::class]);
        [$s1, $s2] = $this->exam('qiyas', 2);
        app(FinishAttempt::class)->execute($this->start($s1)->id);
        app(FinishAttempt::class)->execute($this->start($s2)->id);

        $flags = [];
        Event::assertDispatched(ProgramAttemptCompleted::class, function ($e) use (&$flags) {
            $flags[] = $e->examCompleted;

            return true;
        });
        $this->assertSame([false, true], $flags);
    }

    public function test_scoring_per_program(): void
    {
        Event::fake([ProgramAttemptCompleted::class]);
        foreach (['qiyas' => 2, 'nafis' => 3, 'tahsili' => 4] as $program => $correct) {
            [$es] = $this->exam($program);
            $a = $this->start($es);
            $this->answerAll($a, $correct);
            $this->assertEquals($correct * 25.0, app(FinishAttempt::class)->execute($a->id)->score_pct, $program);
        }
        Event::assertDispatchedTimes(ProgramAttemptCompleted::class, 3);
    }

    public function test_overdue_closes_on_read_path(): void
    {
        Event::fake([ProgramAttemptCompleted::class]);
        [$es] = $this->exam();
        $a = $this->start($es);
        $this->answerAll($a, 2);
        Carbon::setTestNow(now()->addMinutes(31));

        $read = app(AttemptRepository::class)->findForStudent($a->id, $this->student);

        $this->assertSame('timed_out', $read->status);
        $this->assertEquals(50.0, $read->score_pct);
        $this->assertSame(1800, $read->time_spent_seconds);
        Event::assertDispatchedTimes(ProgramAttemptCompleted::class, 1);
        Event::assertDispatched(ProgramAttemptCompleted::class, fn ($e) => $e->status === 'timed_out');
    }

    public function test_finalize_overdue_command(): void
    {
        Event::fake([ProgramAttemptCompleted::class]);
        [$es] = $this->exam();
        $a = $this->start($es);
        Artisan::call('special-programs:finalize-overdue');
        $this->assertSame('in_progress', $a->fresh()->status);

        Carbon::setTestNow(now()->addMinutes(31));
        Artisan::call('special-programs:finalize-overdue');
        Artisan::call('special-programs:finalize-overdue');

        $this->assertSame('timed_out', $a->fresh()->status);
        Event::assertDispatchedTimes(ProgramAttemptCompleted::class, 1);
    }

    public function test_anti_cheat_auto_ends_at_max_exits(): void
    {
        Event::fake([ProgramAttemptCompleted::class]);
        [$es] = $this->exam('qiyas', 1, ['max_exit_attempts' => 2]);
        $a = $this->start($es);

        $this->assertSame('in_progress', app(LogExit::class)->execute($a->id, $this->student)->status);
        $r = app(LogExit::class)->execute($a->id, $this->student);

        $this->assertSame('auto_ended', $r->status);
        $this->assertSame(2, $r->exit_count);
        Event::assertDispatched(ProgramAttemptCompleted::class, fn ($e) => $e->status === 'auto_ended');
    }

    public function test_sequential_lock_expiry_eligibility_and_closed(): void
    {
        [$s1, $s2] = $this->exam('qiyas', 2);
        try {
            $this->start($s2);
            $this->fail('lock not enforced');
        } catch (AttemptException $e) {
            $this->assertSame('locked', $e->reason);
        }
        $a = $this->start($s1);
        app(FinishAttempt::class)->execute($a->id);
        $this->assertSame('in_progress', $this->start($s2)->status);
        try {
            $this->start($s1);
            $this->fail('restart of closed subject');
        } catch (AttemptException $e) {
            $this->assertSame('already_finished', $e->reason);
        }

        [$expired] = $this->exam('qiyas', 1, ['expires_at' => now()->subMinute()]);
        $this->assertReason('expired', $expired);

        [$other] = $this->exam();
        $this->eligible = false;
        $this->assertReason('not_eligible', $other);
    }

    public function test_expiry_does_not_block_resuming_an_open_attempt(): void
    {
        [$es] = $this->exam('qiyas', 1, ['expires_at' => now()->addMinutes(5)]);
        $a = $this->start($es);
        Carbon::setTestNow(now()->addMinutes(10));
        $this->assertSame($a->id, $this->start($es)->id);
    }

    private function assertReason(string $reason, int $esId): void
    {
        try {
            $this->start($esId);
            $this->fail("expected $reason");
        } catch (AttemptException $e) {
            $this->assertSame($reason, $e->reason);
        }
    }
}
