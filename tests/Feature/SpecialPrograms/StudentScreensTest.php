<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Picking\NafisPicker;
use App\Modules\SpecialPrograms\Actions\Picking\QiyasPicker;
use App\Modules\SpecialPrograms\Actions\Picking\QuestionPicker;
use App\Modules\SpecialPrograms\Actions\Picking\TahsiliPicker;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class StudentScreensTest extends TestCase
{
    use LoadsSpMigrations;

    private School $school;

    private User $student;

    private int $examId;

    /** @var int[] exam_subject ids */
    private array $es;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
        Carbon::setTestNow('2026-11-20 10:00:00');
        $this->school = School::factory()->create();
        $year = AcademicYear::create(['name' => 'Y', 'school_id' => $this->school->id, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonth(), 'is_current' => true]);
        $section = Section::factory()->create(['school_id' => $this->school->id]);
        $class = ClassRoom::factory()->create(['section_id' => $section->id, 'academic_year_id' => $year->id, 'grade_level' => 3, 'name' => 'A1', 'division' => null]);
        $this->student = $this->createStudent($this->school);
        $this->student->update(['class_room_id' => $class->id]);
        SpEnrollment::create(['school_id' => $this->school->id, 'program' => 'qiyas', 'student_id' => $this->student->id, 'enrolled_by' => $this->student->id, 'enrolled_at' => now()]);

        $this->examId = DB::table('sp_exams')->insertGetId(['school_id' => $this->school->id, 'program' => 'qiyas', 'title' => 'اختبار تجريبي', 'type' => 'multi',
            'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => 1, 'creator_role' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sp_exam_targets')->insert(['exam_id' => $this->examId, 'class_id' => $class->id]);
        $subj = DB::table('sp_subjects')->where('program', 'qiyas')->orderBy('id')->pluck('id');
        foreach ([0, 1] as $i) {
            $this->es[] = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $this->examId, 'sp_subject_id' => $subj[$i], 'duration_minutes' => 30,
                'total_questions' => 3, 'order_index' => $i + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->mock(QuestionPoolRepository::class, fn ($m) => $m->shouldReceive('snapshot')->andReturnUsing(function (array $ids) {
            $out = [];
            foreach ($ids as $id) {
                $out[$id] = ['bank_question_id' => 1000 + $id, 'stem' => "سؤال$id", 'image_path' => null,
                    'options' => [['text' => 'خيار أ', 'image' => null], ['text' => 'خيار ب', 'image' => null]],
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

    private function begin(int $i = 0): SpAttempt
    {
        $this->actingAs($this->student)->post(route('student.programs.start', [$this->examId, $this->es[$i]]))->assertRedirect();

        return SpAttempt::where('exam_subject_id', $this->es[$i])->firstOrFail();
    }

    public function test_list_exam_and_start_render_items(): void
    {
        $this->actingAs($this->student)->get(route('student.programs.index'))->assertOk()->assertSee('اختبار تجريبي');
        $this->get(route('student.programs.exam', $this->examId))->assertOk();
        $a = $this->begin();
        $page = $this->get(route('student.programs.take', $a->id))->assertOk()->assertSee('سؤال1')->assertSee('خيار ب')->assertSee('1800', false);
        $this->assertStringNotContainsString('correct_index', $page->getContent());
    }

    public function test_second_subject_locked_until_first_done(): void
    {
        $this->actingAs($this->student)->post(route('student.programs.start', [$this->examId, $this->es[1]]))
            ->assertRedirect(route('student.programs.exam', $this->examId))->assertSessionHas('error');
        $this->assertSame(0, SpAttempt::count());
    }

    public function test_answer_autosave_json_never_reveals_correctness(): void
    {
        $a = $this->begin();
        $item = $a->items()->first();
        $this->postJson(route('student.programs.answer', $a->id), ['item_id' => $item->id, 'option' => 1])
            ->assertOk()->assertJson(['saved' => true])->assertJsonMissingPath('is_correct');
        $this->assertSame(1, (int) $item->fresh()->answer_index);
        $this->getJson(route('student.programs.heartbeat', $a->id))->assertOk()->assertJson(['status' => 'in_progress', 'remaining_seconds' => 1800]);
    }

    public function test_late_answer_is_rejected_with_message(): void
    {
        $a = $this->begin();
        $item = $a->items()->first();
        Carbon::setTestNow(now()->addMinutes(31));
        $this->postJson(route('student.programs.answer', $a->id), ['item_id' => $item->id, 'option' => 0])
            ->assertStatus(422)->assertJson(['saved' => false, 'reason' => 'late', 'message' => 'انتهى وقت الاختبار ولم تُحفظ الإجابة']);
        $this->assertSame('timed_out', $a->fresh()->status);
    }

    public function test_finish_then_result_and_final(): void
    {
        $a = $this->begin();
        $first = $a->items()->first();
        $this->postJson(route('student.programs.answer', $a->id), ['item_id' => $first->id, 'option' => 0])->assertOk();
        $this->get(route('student.programs.result', $a->id))->assertRedirect(route('student.programs.take', $a->id));
        $this->postJson(route('student.programs.finish', $a->id))->assertOk()->assertJson(['status' => 'completed']);
        $this->get(route('student.programs.result', $a->id))->assertOk()->assertSee('33.33')->assertSee('خيار أ');
        $this->get(route('student.programs.take', $a->id))->assertRedirect(route('student.programs.result', $a->id));
        $this->get(route('student.programs.final', $this->examId))->assertRedirect(route('student.programs.exam', $this->examId));

        $b = $this->begin(1);
        $this->post(route('student.programs.finish', $b->id))->assertRedirect(route('student.programs.result', $b->id));
        $this->get(route('student.programs.final', $this->examId))->assertOk()->assertSee('16.67');
        $this->get(route('student.programs.report', 'qiyas'))->assertOk()->assertSee(route('student.programs.result', $a->id));
    }

    public function test_exit_beacon_increments_and_can_auto_end(): void
    {
        DB::table('sp_exams')->where('id', $this->examId)->update(['max_exit_attempts' => 2]);
        $a = $this->begin();
        $this->postJson(route('student.programs.exit', $a->id))->assertOk()->assertJson(['status' => 'in_progress', 'exit_count' => 1]);
        $this->postJson(route('student.programs.exit', $a->id))->assertOk()->assertJson(['status' => 'auto_ended']);
    }

    public function test_foreign_and_unenrolled_students_are_blocked(): void
    {
        $a = $this->begin();
        $other = User::factory()->create(['school_id' => $this->school->id, 'email' => 'o@t.com']);
        $other->roles()->attach(\App\Models\Role::where('slug', 'student')->first());
        $this->actingAs($other)->get(route('student.programs.take', $a->id))->assertNotFound();
        $this->get(route('student.programs.exam', $this->examId))->assertNotFound();
        $this->post(route('student.programs.start', [$this->examId, $this->es[0]]))->assertRedirect(route('student.programs.exam', $this->examId));
        $this->postJson(route('student.programs.answer', $a->id), ['item_id' => 1, 'option' => 0])->assertNotFound();
        $this->get(route('student.programs.index'))->assertOk()->assertDontSee('اختبار تجريبي');
    }
}
