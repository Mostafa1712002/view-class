<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\School;
use App\Modules\SpecialPrograms\Actions\Enrollment\AssignTeacher;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Models\SpTeacher;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class GroupsTest extends TestCase
{
    use LoadsSpMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
    }

    /** TestCase helpers hard-code one email per role; these are unique. */
    private function person(string $role, School $school)
    {
        $user = \App\Models\User::factory()->create(['school_id' => $school->id]);
        $user->roles()->attach(\App\Models\Role::where('slug', $role)->first());

        return $user;
    }

    private function enroll(School $school, $student, ?int $groupId = null, string $program = 'qiyas'): SpEnrollment
    {
        return SpEnrollment::create(['school_id' => $school->id, 'program' => $program, 'student_id' => $student->id,
            'group_id' => $groupId, 'enrolled_at' => now()]);
    }

    private function group(School $school, string $name = 'G', ?int $teacherId = null): SpGroup
    {
        return SpGroup::create(['school_id' => $school->id, 'program' => 'qiyas', 'name' => $name, 'teacher_id' => $teacherId]);
    }

    private function subjectId(string $code): int
    {
        return (int) DB::table('sp_subjects')->where('code', $code)->value('id');
    }

    public function test_pages_render_for_school_admin_and_super_admin_without_school(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $group = $this->group($school);
        $this->actingAs($admin);
        $this->get(route('manage.special-programs.groups.index', 'qiyas'))->assertOk()->assertSee($group->name);
        $this->get(route('manage.special-programs.groups.create', 'qiyas'))->assertOk();
        $this->get(route('manage.special-programs.groups.show', ['qiyas', $group->id]))->assertOk();
        $this->get(route('manage.special-programs.groups.edit', ['qiyas', $group->id]))->assertOk();
        $this->get(route('manage.special-programs.teachers.index', 'qiyas'))->assertOk();

        $this->actingAs($this->createSuperAdmin())->withSession(['scope.school_id' => null]);
        $this->get(route('manage.special-programs.groups.index', 'qiyas'))->assertOk()->assertSee('alert-warning', false);
        $this->get(route('manage.special-programs.teachers.index', 'qiyas'))->assertOk()->assertSee('alert-warning', false);
        $this->post(route('manage.special-programs.groups.store', 'qiyas'), ['name' => 'X'])->assertStatus(422);
    }

    public function test_students_and_teachers_cannot_manage_groups(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->person('student', $school))->get(route('manage.special-programs.groups.index', 'qiyas'))->assertForbidden();
        $this->actingAs($this->person('teacher', $school))->get(route('manage.special-programs.groups.index', 'qiyas'))->assertForbidden();
    }

    public function test_create_group_validates_teacher_belongs_to_school(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $mine = $this->person('teacher', $school);
        $foreign = $this->person('teacher', $other);

        $this->actingAs($admin)->post(route('manage.special-programs.groups.store', 'qiyas'), ['name' => 'Bad', 'teacher_id' => $foreign->id])
            ->assertSessionHasErrors('teacher_id');
        $this->assertSame(0, SpGroup::count());

        $this->post(route('manage.special-programs.groups.store', 'qiyas'), ['name' => 'Good', 'teacher_id' => $mine->id, 'target_level' => 'weak'])
            ->assertRedirect();
        $this->assertSame(1, SpGroup::where('school_id', $school->id)->where('teacher_id', $mine->id)->count());
    }

    public function test_members_must_be_enrolled_and_a_student_has_one_group_per_program(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $a = $this->person('student', $school);
        $b = $this->person('student', $school);
        $notEnrolled = $this->person('student', $school);
        $g1 = $this->group($school, 'G1');
        $g2 = $this->group($school, 'G2');
        $this->enroll($school, $a);
        $this->enroll($school, $b);

        $this->actingAs($admin)->post(route('manage.special-programs.groups.members.store', ['qiyas', $g1->id]), ['student_ids' => [$a->id, $notEnrolled->id]])
            ->assertSessionHas('warning');
        $this->assertSame($g1->id, SpEnrollment::where('student_id', $a->id)->value('group_id'));
        $this->assertSame(0, SpEnrollment::where('student_id', $notEnrolled->id)->count());

        // moving: adding to G2 takes him out of G1 (single group_id)
        $this->post(route('manage.special-programs.groups.members.store', ['qiyas', $g2->id]), ['student_ids' => [$a->id]]);
        $this->assertSame($g2->id, SpEnrollment::where('student_id', $a->id)->value('group_id'));
        $this->assertSame(0, SpEnrollment::where('group_id', $g1->id)->count());

        $this->delete(route('manage.special-programs.groups.members.destroy', ['qiyas', $g2->id, $a->id]));
        $this->assertNull(SpEnrollment::where('student_id', $a->id)->value('group_id'));
        $this->assertSame(1, SpEnrollment::where('student_id', $a->id)->count(), 'removal ungroups, stays enrolled');
    }

    public function test_level_filter_uses_latest_attempt_percentage(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $strong = $this->person('student', $school);
        $weak = $this->person('student', $school);
        $group = $this->group($school);
        $this->enroll($school, $strong);
        $this->enroll($school, $weak);
        $subject = $this->subjectId('qiyas_verbal');
        $examId = DB::table('sp_exams')->insertGetId(['school_id' => $school->id, 'program' => 'qiyas', 'title' => 'E', 'type' => 'single',
            'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => 1, 'creator_role' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
        $es = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $examId, 'sp_subject_id' => $subject, 'duration_minutes' => 30, 'total_questions' => 4, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([[$strong, 95], [$weak, 20]] as [$student, $pct]) {
            DB::table('sp_attempts')->insert(['school_id' => $school->id, 'program' => 'qiyas', 'exam_id' => $examId, 'exam_subject_id' => $es,
                'student_id' => $student->id, 'status' => 'completed', 'started_at' => now()->subHour(), 'deadline_at' => now(), 'ended_at' => now(),
                'score_pct' => $pct, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->actingAs($admin)->get(route('manage.special-programs.groups.show', ['qiyas', $group->id, 'level' => 'excellent']))
            ->assertOk()->assertSee($strong->name)->assertDontSee($weak->name);
        $this->get(route('manage.special-programs.groups.show', ['qiyas', $group->id, 'level' => 'weak']))
            ->assertOk()->assertSee($weak->name)->assertDontSee($strong->name);
    }

    public function test_cross_school_ids_are_blocked(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $foreignGroup = $this->group($other, 'Foreign');
        $foreignStudent = $this->person('student', $other);
        $this->enroll($other, $foreignStudent);
        $mine = $this->group($school);

        $this->actingAs($admin);
        $this->get(route('manage.special-programs.groups.show', ['qiyas', $foreignGroup->id]))->assertNotFound();
        $this->put(route('manage.special-programs.groups.update', ['qiyas', $foreignGroup->id]), ['name' => 'x'])->assertNotFound();
        $this->delete(route('manage.special-programs.groups.destroy', ['qiyas', $foreignGroup->id]))->assertNotFound();
        $this->post(route('manage.special-programs.groups.members.store', ['qiyas', $foreignGroup->id]), ['student_ids' => [$foreignStudent->id]])->assertNotFound();

        $this->post(route('manage.special-programs.groups.members.store', ['qiyas', $mine->id]), ['student_ids' => [$foreignStudent->id]]);
        $this->assertNull(SpEnrollment::where('student_id', $foreignStudent->id)->value('group_id'), 'foreign enrolment untouched');

        $foreignRow = SpTeacher::create(['school_id' => $other->id, 'program' => 'qiyas', 'teacher_id' => $this->person('teacher', $other)->id]);
        $this->delete(route('manage.special-programs.teachers.destroy', ['qiyas', $foreignRow->id]))->assertNotFound();
        $this->assertNotNull($foreignRow->fresh());
    }

    public function test_teacher_assignment_per_subject(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $teacher = $this->person('teacher', $school);
        $verbal = $this->subjectId('qiyas_verbal');
        $quant = $this->subjectId('qiyas_quant');
        $repo = app(GroupRepository::class);
        $this->assertSame([], $repo->teacherSubjectIds($school->id, $teacher->id, 'qiyas'));

        $this->actingAs($admin)->post(route('manage.special-programs.teachers.store', 'qiyas'), ['teacher_id' => $teacher->id, 'sp_subject_id' => $verbal])->assertRedirect();
        $this->assertSame([$verbal], $repo->teacherSubjectIds($school->id, $teacher->id, 'qiyas'));

        $this->post(route('manage.special-programs.teachers.store', 'qiyas'), ['teacher_id' => $teacher->id, 'sp_subject_id' => $quant]);
        $this->assertEqualsCanonicalizing([$verbal, $quant], $repo->teacherSubjectIds($school->id, $teacher->id, 'qiyas'));

        // a subject of another program is rejected
        $this->post(route('manage.special-programs.teachers.store', 'qiyas'), ['teacher_id' => $teacher->id, 'sp_subject_id' => $this->subjectId('nafis_math')])
            ->assertSessionHasErrors('sp_subject_id');

        // «all subjects» replaces the single rows
        $this->post(route('manage.special-programs.teachers.store', 'qiyas'), ['teacher_id' => $teacher->id]);
        $this->assertNull($repo->teacherSubjectIds($school->id, $teacher->id, 'qiyas'));
        $this->assertSame(1, SpTeacher::count());

        // single after «all» is refused
        $this->post(route('manage.special-programs.teachers.store', 'qiyas'), ['teacher_id' => $teacher->id, 'sp_subject_id' => $verbal])
            ->assertSessionHasErrors('sp_subject_id');

        // a non-teacher / foreign-school user cannot be assigned
        $this->expectException(ValidationException::class);
        app(AssignTeacher::class)->execute($school->id, 'qiyas', $this->person('teacher', School::factory()->create())->id, null, $admin->id);
    }

    public function test_groups_led_by_and_for_school(): void
    {
        $school = School::factory()->create();
        $t1 = $this->person('teacher', $school);
        $t2 = $this->person('teacher', $school);
        $a = $this->group($school, 'A', $t1->id);
        $this->group($school, 'B', $t2->id);
        $repo = app(GroupRepository::class);

        $this->assertSame([$a->id], $repo->groupIdsLedBy($t1->id, 'qiyas'));
        $this->assertSame(['A'], $repo->forSchool($school->id, 'qiyas', $t1->id)->pluck('name')->all());
        $this->assertCount(2, $repo->forSchool($school->id, 'qiyas'));
        $this->assertCount(0, $repo->forSchool($school->id, 'nafis'));
    }
}
