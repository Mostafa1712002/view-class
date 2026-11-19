<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class ExamBuilderTest extends TestCase
{
    use LoadsSpMigrations;

    private int $bq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
    }

    private function subjectId(string $code): int
    {
        return (int) DB::table('sp_subjects')->where('code', $code)->value('id');
    }

    private function seedPool(string $code, int $count = 6): int
    {
        $sid = $this->subjectId($code);
        $skill = DB::table('sp_skills')->insertGetId(['sp_subject_id' => $sid, 'name' => 'S'.$sid, 'created_at' => now(), 'updated_at' => now()]);
        for ($i = 1; $i <= $count; $i++) {
            SpQuestion::create(['bank_question_id' => ++$this->bq, 'sp_subject_id' => $sid, 'sp_skill_id' => $skill, 'content_hash' => md5((string) $this->bq),
                'term' => 'first', 'week' => 1, 'question_order' => $i, 'difficulty' => 'easy']);
        }

        return $skill;
    }

    private function school(): array
    {
        $school = School::factory()->create();
        $class = ClassRoom::factory()->create(['section_id' => Section::factory()->create(['school_id' => $school->id])->id, 'grade_level' => 3]);

        return [$school, $class];
    }

    private function qiyasPayload(int $classId, int $skill, array $over = []): array
    {
        return array_replace_recursive([
            'title' => 'اختبار', 'type' => 'single', 'target_class_ids' => [$classId], 'expiry_hours' => 24,
            'subjects' => [['sp_subject_id' => $this->subjectId('qiyas_verbal'), 'duration_minutes' => 30, 'total_questions' => 4,
                'term' => 'first', 'weeks' => [1], 'shares' => [$skill => 60]]],
        ], $over);
    }

    private function url(string $name, string $program = 'qiyas', array $extra = []): string
    {
        return route('manage.special-programs.'.$name, array_merge([$program], $extra));
    }

    public function test_admin_creates_exam_and_expiry_is_saved(): void
    {
        [$school, $class] = $this->school();
        $skill = $this->seedPool('qiyas_verbal');
        $admin = $this->createSchoolAdmin($school);

        $this->actingAs($admin)->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill))->assertRedirect();

        $exam = SpExam::firstOrFail();
        $this->assertSame('admin', $exam->creator_role);
        $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $exam->expires_at->timestamp, 5);
        $this->assertSame(1, DB::table('sp_exam_targets')->where('exam_id', $exam->id)->where('class_id', $class->id)->count());
        $this->assertSame(60, (int) DB::table('sp_exam_skill_shares')->value('percentage'));
        $this->actingAs($admin)->get($this->url('exams.show', 'qiyas', [$exam->id]))->assertOk()->assertSee('اختبار');
        $this->get($this->url('exams.index'))->assertOk()->assertSee('اختبار');
        foreach (['qiyas', 'tahsili', 'nafis'] as $program) {
            $this->get($this->url('exams.create', $program))->assertOk();
        }
    }

    public function test_share_over_100_is_rejected_and_nothing_is_written(): void
    {
        [$school, $class] = $this->school();
        $skill = $this->seedPool('qiyas_verbal');
        $this->actingAs($this->createSchoolAdmin($school))
            ->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['subjects' => [['shares' => [$skill => 101]]]]))
            ->assertSessionHasErrors();
        $this->assertSame(0, SpExam::count());
        $this->assertSame(0, DB::table('sp_exam_subjects')->count());
        $this->assertSame(0, DB::table('sp_exam_targets')->count());
    }

    public function test_more_questions_than_the_bank_holds_is_rejected(): void
    {
        [$school, $class] = $this->school();
        $skill = $this->seedPool('qiyas_verbal', 3);
        $this->actingAs($this->createSchoolAdmin($school))
            ->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['subjects' => [['total_questions' => 4]]]))
            ->assertSessionHasErrors('subjects.0.total_questions');
        $this->assertSame(0, SpExam::count());
    }

    public function test_tahsili_percentages_must_total_100_and_range_is_saved(): void
    {
        [$school, $class] = $this->school();
        $this->seedPool('tahsili_physics', 10);
        $sid = $this->subjectId('tahsili_physics');
        $row = ['sp_subject_id' => $sid, 'duration_minutes' => 30, 'total_questions' => 5, 'easy_pct' => 40, 'medium_pct' => 40, 'hard_pct' => 30, 'range_start' => 2, 'range_end' => 9];
        $admin = $this->createSchoolAdmin($school);
        $post = fn (array $r) => $this->actingAs($admin)->post($this->url('exams.store', 'tahsili'),
            ['title' => 'T', 'type' => 'single', 'target_class_ids' => [$class->id], 'expiry_hours' => 48, 'subjects' => [$r]]);

        $post($row)->assertSessionHasErrors('subjects.0.easy_pct');
        $this->assertSame(0, SpExam::count());
        $post(['hard_pct' => 20] + $row)->assertRedirect();
        $es = DB::table('sp_exam_subjects')->first();
        $this->assertSame([2, 9], [(int) $es->range_start, (int) $es->range_end]);
    }

    public function test_teacher_limits_subject_class_group_and_question_count(): void
    {
        [$school, $class] = $this->school();
        $skill = $this->seedPool('qiyas_verbal');
        $this->seedPool('qiyas_quant');
        $teacher = $this->createTeacher($school);
        $class->update(['lead_teacher_id' => $teacher->id]);
        $foreignClass = ClassRoom::factory()->create(['section_id' => $class->section_id]);
        DB::table('sp_teachers')->insert(['school_id' => $school->id, 'program' => 'qiyas', 'sp_subject_id' => $this->subjectId('qiyas_verbal'),
            'teacher_id' => $teacher->id, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $foreignGroup = DB::table('sp_groups')->insertGetId(['school_id' => $school->id, 'program' => 'qiyas', 'name' => 'g', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($teacher);

        $this->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['subjects' => [['sp_subject_id' => $this->subjectId('qiyas_quant')]]]))
            ->assertSessionHasErrors('subjects.0.sp_subject_id');
        $this->post($this->url('exams.store'), $this->qiyasPayload($foreignClass->id, $skill))->assertSessionHasErrors('target_class_ids');
        $this->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['target_group_ids' => [$foreignGroup]]))->assertSessionHasErrors('target_group_ids');
        $this->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['subjects' => [['total_questions' => 101]]]))->assertSessionHasErrors('subjects.0.total_questions');
        $this->assertSame(0, SpExam::count());

        $this->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['type' => 'multi']))->assertRedirect();
        $exam = SpExam::firstOrFail();
        $this->assertSame(['teacher', 'single'], [$exam->creator_role, $exam->type]);
        $this->assertSame($teacher->id, (int) $exam->teacher_id);
    }

    public function test_teacher_sees_only_own_exams_and_other_school_is_blocked(): void
    {
        [$school, $class] = $this->school();
        $skill = $this->seedPool('qiyas_verbal');
        $admin = $this->createSchoolAdmin($school);
        $teacher = $this->createTeacher($school);
        $this->actingAs($admin)->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['title' => 'AdminExam']));
        $exam = SpExam::firstOrFail();

        $this->actingAs($teacher)->get($this->url('exams.index'))->assertOk()->assertDontSee('AdminExam');
        $this->get($this->url('exams.show', 'qiyas', [$exam->id]))->assertNotFound();

        $other = User::factory()->create(['school_id' => School::factory()->create()->id, 'email' => 'other@test.com']);
        $other->roles()->attach(\App\Models\Role::where('slug', 'school-admin')->first());
        $this->actingAs($other)->get($this->url('exams.show', 'qiyas', [$exam->id]))->assertNotFound();
        $this->delete($this->url('exams.destroy', 'qiyas', [$exam->id]))->assertNotFound();
    }

    public function test_delete_is_soft_and_keeps_attempts(): void
    {
        [$school, $class] = $this->school();
        $skill = $this->seedPool('qiyas_verbal');
        $admin = $this->createSchoolAdmin($school);
        $student = $this->createStudent($school);
        $this->actingAs($admin)->post($this->url('exams.store'), $this->qiyasPayload($class->id, $skill, ['title' => 'ExamZ1']));
        $exam = SpExam::firstOrFail();
        $esId = (int) DB::table('sp_exam_subjects')->value('id');
        DB::table('sp_attempts')->insert(['school_id' => $school->id, 'program' => 'qiyas', 'exam_id' => $exam->id, 'exam_subject_id' => $esId,
            'student_id' => $student->id, 'status' => 'completed', 'started_at' => now(), 'deadline_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $this->delete($this->url('exams.destroy', 'qiyas', [$exam->id]))->assertRedirect();
        $this->assertSoftDeleted('sp_exams', ['id' => $exam->id]);
        $this->assertSame(1, DB::table('sp_attempts')->count());
        $this->get($this->url('exams.index'))->assertDontSee('ExamZ1');
    }

    public function test_audience_is_enrolled_students_in_class_or_group(): void
    {
        [$school, $class] = $this->school();
        $otherClass = ClassRoom::factory()->create(['section_id' => $class->section_id]);
        $exam = SpExam::create(['school_id' => $school->id, 'program' => 'qiyas', 'title' => 'x', 'type' => 'single', 'expiry_hours' => 1,
            'expires_at' => now()->addDay(), 'created_by' => 1, 'creator_role' => 'admin']);
        $groupId = DB::table('sp_groups')->insertGetId(['school_id' => $school->id, 'program' => 'qiyas', 'name' => 'g', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $exam->targets()->create(['class_id' => $class->id]);
        $exam->targets()->create(['group_id' => $groupId]);

        $mk = fn (?ClassRoom $c, bool $enrolled, ?int $group = null) => tap(User::factory()->create(['school_id' => $school->id, 'class_room_id' => $c?->id]), function ($u) use ($school, $enrolled, $group) {
            $enrolled && DB::table('sp_enrollments')->insert(['school_id' => $school->id, 'program' => 'qiyas', 'student_id' => $u->id, 'group_id' => $group,
                'enrolled_by' => 1, 'enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        });
        $inClass = $mk($class, true);
        $inGroup = $mk($otherClass, true, $groupId);
        $notEnrolled = $mk($class, false);
        $outside = $mk($otherClass, true);

        $repo = app(ExamRepository::class);
        $ids = $repo->audienceQuery($exam)->pluck('users.id')->all();
        sort($ids);
        $expected = [$inClass->id, $inGroup->id];
        sort($expected);
        $this->assertSame($expected, $ids);
        $this->assertTrue($repo->isInAudience($exam, $inGroup));
        $this->assertFalse($repo->isInAudience($exam, $notEnrolled));
        $this->assertFalse($repo->isInAudience($exam, $outside));
        $this->assertSame([$exam->id], $repo->forStudent($inClass)->pluck('id')->all());
        $this->assertSame([], $repo->forStudent($outside)->pluck('id')->all());
    }

    public function test_bank_stats_json_and_students_cannot_reach_builder(): void
    {
        [$school] = $this->school();
        $this->seedPool('tahsili_physics', 10);
        $sid = $this->subjectId('tahsili_physics');
        $this->actingAs($this->createSchoolAdmin($school))
            ->getJson($this->url('bank-stats', 'tahsili', ['sp_subject_id' => $sid, 'range_start' => 3, 'range_end' => 5]))
            ->assertOk()->assertJsonPath('total', 10)->assertJsonPath('in_range', 3)->assertJsonPath('max_order', 10);
        $this->actingAs($this->createStudent($school))->get($this->url('exams.index'))->assertForbidden();
    }
}
