<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Models\SpGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

/** T9.1 cross-cutting access: role walls, cross-school ids, teacher/parent/student ownership. */
class AccessTest extends TestCase
{
    use LoadsSpMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
    }

    private function person(string $role, School $school): User
    {
        $u = User::factory()->create(['school_id' => $school->id]);
        $u->roles()->attach(Role::where('slug', $role)->first());

        return $u;
    }

    /** Exam of $school with one subject; optional closed attempt for $student. @return array{exam:int, es:int, attempt:?int} */
    private function exam(School $school, User $owner, ?User $student = null, string $role = 'teacher', string $title = 'E'): array
    {
        $subjectId = (int) DB::table('sp_subjects')->where('program', 'qiyas')->orderBy('id')->value('id');
        $exam = DB::table('sp_exams')->insertGetId(['school_id' => $school->id, 'program' => 'qiyas', 'title' => $title, 'type' => 'single',
            'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => $owner->id, 'creator_role' => $role,
            'teacher_id' => $role === 'teacher' ? $owner->id : null, 'created_at' => now(), 'updated_at' => now()]);
        $es = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $exam, 'sp_subject_id' => $subjectId,
            'teacher_id' => $role === 'teacher' ? $owner->id : null, 'duration_minutes' => 30, 'total_questions' => 4,
            'created_at' => now(), 'updated_at' => now()]);
        $attempt = $student ? DB::table('sp_attempts')->insertGetId(['school_id' => $school->id, 'program' => 'qiyas', 'exam_id' => $exam,
            'exam_subject_id' => $es, 'student_id' => $student->id, 'status' => 'completed', 'started_at' => now()->subHour(),
            'deadline_at' => now(), 'ended_at' => now(), 'items_count' => 4, 'correct_count' => 2, 'score_pct' => 50,
            'created_at' => now(), 'updated_at' => now()]) : null;

        return ['exam' => $exam, 'es' => $es, 'attempt' => $attempt];
    }

    private function enrol(User $student, ?int $groupId = null): SpEnrollment
    {
        return SpEnrollment::create(['school_id' => $student->school_id, 'program' => 'qiyas', 'student_id' => $student->id,
            'group_id' => $groupId, 'enrolled_by' => $student->id, 'enrolled_at' => now()]);
    }

    /** Every GET route under manage.special-programs.*, params filled with $ids (program defaults to qiyas). */
    private function staffGetUrls(array $ids = [], array $skipPrefixes = []): array
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'manage.special-programs.') || ! in_array('GET', $route->methods(), true)) {
                continue;
            }
            foreach ($skipPrefixes as $skip) {
                if (str_starts_with($name, $skip)) {
                    continue 2;
                }
            }
            $params = [];
            foreach ($route->parameterNames() as $p) {
                $params[$p] = $p === 'program' ? 'qiyas' : ($ids[$p] ?? 1);
            }
            $urls[$name] = route($name, $params);
        }

        return $urls;
    }

    public function test_students_and_parents_get_403_on_every_staff_get_route(): void
    {
        $school = School::factory()->create();
        $urls = $this->staffGetUrls();
        $this->assertGreaterThan(30, count($urls));
        foreach (['student', 'parent'] as $role) {
            $user = $this->person($role, $school);
            foreach ($urls as $name => $url) {
                $this->actingAs($user)->get($url)->assertStatus(403, "$role on $name");
            }
        }
    }

    public function test_super_admin_only_settings_are_403_for_every_other_role(): void
    {
        $school = School::factory()->create();
        foreach (['school-admin', 'teacher', 'student', 'parent'] as $role) {
            $user = $this->person($role, $school);
            $this->actingAs($user)->get(route('manage.special-programs.settings.global', ['program' => 'qiyas']))->assertForbidden();
            $this->actingAs($user)->put(route('manage.special-programs.settings.global.update'), ['program' => 'qiyas'])->assertForbidden();
        }
        $this->actingAs($this->createSuperAdmin())->get(route('manage.special-programs.settings.global', ['program' => 'qiyas']))->assertOk();
    }

    public function test_school_a_gets_404_on_school_b_ids(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $adminA = $this->person('school-admin', $a);
        $adminB = $this->person('school-admin', $b);
        $teacherB = $this->person('teacher', $b);
        $studentB = $this->person('student', $b);
        $studentB->update(['name' => 'SCHOOL-B-STUDENT']);
        $groupB = SpGroup::create(['school_id' => $b->id, 'program' => 'qiyas', 'name' => 'GB', 'created_by' => $adminB->id]);
        $enrolB = $this->enrol($studentB, $groupB->id);
        $teacherRowB = DB::table('sp_teachers')->insertGetId(['school_id' => $b->id, 'program' => 'qiyas', 'teacher_id' => $teacherB->id,
            'created_by' => $adminB->id, 'created_at' => now(), 'updated_at' => now()]);
        $examB = $this->exam($b, $adminB, $studentB, 'admin', 'EXAM-OF-B')['exam'];

        $this->actingAs($adminA);
        foreach ([
            route('manage.special-programs.exams.show', ['qiyas', $examB]),
            route('manage.special-programs.exams.export', ['qiyas', $examB]),
            route('manage.special-programs.groups.show', ['qiyas', $groupB->id]),
            route('manage.special-programs.groups.edit', ['qiyas', $groupB->id]),
            route('manage.special-programs.student-report', ['qiyas', $studentB->id]),
            route('manage.special-programs.student-report.pdf', ['qiyas', $studentB->id]),
        ] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->delete(route('manage.special-programs.exams.destroy', ['qiyas', $examB]))->assertNotFound();
        $this->delete(route('manage.special-programs.enrollments.destroy', ['qiyas', $enrolB->id]))->assertNotFound();
        $this->delete(route('manage.special-programs.groups.destroy', ['qiyas', $groupB->id]))->assertNotFound();
        $this->post(route('manage.special-programs.groups.members.store', ['qiyas', $groupB->id]), ['student_ids' => [$studentB->id]])->assertNotFound();
        $this->delete(route('manage.special-programs.teachers.destroy', ['qiyas', $teacherRowB]))->assertNotFound();

        // lists and reports of school A never show school B rows
        $this->get(route('manage.special-programs.exams.index', 'qiyas'))->assertOk()->assertDontSee('EXAM-OF-B');
        $this->get(route('manage.special-programs.reports.index', 'qiyas'))->assertOk()->assertDontSee('SCHOOL-B-STUDENT');
        $this->get(route('manage.special-programs.enrollments.index', 'qiyas'))->assertOk()->assertDontSee('SCHOOL-B-STUDENT');

        // nothing of school B was changed
        $this->assertNull(DB::table('sp_exams')->where('id', $examB)->value('deleted_at'));
        $this->assertNull(DB::table('sp_enrollments')->where('id', $enrolB->id)->value('deleted_at'));
        $this->assertNull(DB::table('sp_groups')->where('id', $groupB->id)->value('deleted_at'));
        $this->assertTrue(DB::table('sp_teachers')->where('id', $teacherRowB)->exists());
    }

    public function test_teacher_sees_only_his_own_exams(): void
    {
        $school = School::factory()->create();
        $me = $this->person('teacher', $school);
        $colleague = $this->person('teacher', $school);
        $admin = $this->person('school-admin', $school);
        $student = $this->person('student', $school);
        $mine = $this->exam($school, $me, $student, 'teacher', 'MY-EXAM')['exam'];
        $theirs = $this->exam($school, $colleague, $student, 'teacher', 'COLLEAGUE-EXAM')['exam'];
        $adminExam = $this->exam($school, $admin, $student, 'admin', 'ADMIN-EXAM')['exam'];

        $this->actingAs($me)->get(route('manage.special-programs.exams.index', 'qiyas'))
            ->assertOk()->assertSee('MY-EXAM')->assertDontSee('COLLEAGUE-EXAM')->assertDontSee('ADMIN-EXAM');
        $this->get(route('manage.special-programs.exams.show', ['qiyas', $mine]))->assertOk();
        foreach ([$theirs, $adminExam] as $id) {
            $this->get(route('manage.special-programs.exams.show', ['qiyas', $id]))->assertNotFound();
            $this->get(route('manage.special-programs.exams.export', ['qiyas', $id]))->assertNotFound();
            $this->delete(route('manage.special-programs.exams.destroy', ['qiyas', $id]))->assertNotFound();
        }
        // staff reports: only his exams' attempts
        $this->get(route('manage.special-programs.reports.index', 'qiyas'))->assertOk()
            ->assertSee('MY-EXAM')->assertDontSee('COLLEAGUE-EXAM')->assertDontSee('ADMIN-EXAM');
        // teachers never reach manage-only screens (groups, teachers, enrolment writes) or school settings
        $this->get(route('manage.special-programs.groups.index', 'qiyas'))->assertForbidden();
        $this->get(route('manage.special-programs.teachers.index', 'qiyas'))->assertForbidden();
        $this->get(route('manage.special-programs.enrollments.create', 'qiyas'))->assertForbidden();
        $this->get(route('manage.special-programs.settings.index', 'qiyas'))->assertForbidden();
    }

    public function test_parent_cannot_see_another_child(): void
    {
        $school = School::factory()->create();
        $parent = $this->person('parent', $school);
        $mine = $this->person('student', $school);
        $other = $this->person('student', $school);
        DB::table('parent_student')->insert(['parent_id' => $parent->id, 'student_id' => $mine->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->enrol($mine);
        $this->enrol($other);

        $this->actingAs($parent)->get(route('parent.programs.report', [$mine->id, 'qiyas']))->assertOk();
        $this->get(route('parent.programs.report', [$other->id, 'qiyas']))->assertNotFound();
        $this->get(route('parent.programs.report.pdf', [$other->id, 'qiyas']))->assertNotFound();
    }

    public function test_student_reaches_only_his_own_attempts(): void
    {
        $school = School::factory()->create();
        $owner = $this->person('school-admin', $school);
        $alice = $this->person('student', $school);
        $bob = $this->person('student', $school);
        $attempt = $this->exam($school, $owner, $alice, 'admin')['attempt'];

        $this->actingAs($bob);
        foreach (['take', 'heartbeat', 'result'] as $r) {
            $this->get(route('student.programs.'.$r, $attempt))->assertNotFound();
        }
        foreach (['exit', 'finish'] as $r) {
            $this->post(route('student.programs.'.$r, $attempt))->assertNotFound();
        }
        $this->post(route('student.programs.answer', $attempt), ['item_id' => 1, 'option' => 0])->assertNotFound();
        $this->actingAs($alice)->get(route('student.programs.result', $attempt))->assertOk();
    }

    public function test_super_admin_without_school_scope_never_errors(): void
    {
        $school = School::factory()->create();
        $admin = $this->person('school-admin', $school);
        $student = $this->person('student', $school);
        $group = SpGroup::create(['school_id' => $school->id, 'program' => 'qiyas', 'name' => 'G', 'created_by' => $admin->id]);
        $this->enrol($student, $group->id);
        $e = $this->exam($school, $admin, $student, 'admin');

        $super = $this->createSuperAdmin();
        $super->update(['school_id' => null]);
        // bank.* reads bank_questions (MySQL-only migrations), covered by BankAccessTest; teacher-reports is role:teacher only.
        $urls = $this->staffGetUrls(['id' => $e['exam'], 'studentId' => $student->id],
            ['manage.special-programs.bank.', 'manage.special-programs.teacher-reports.']);
        $urls['manage.special-programs.groups.show'] = route('manage.special-programs.groups.show', ['qiyas', $group->id]);
        $urls['manage.special-programs.groups.edit'] = route('manage.special-programs.groups.edit', ['qiyas', $group->id]);
        $urls['manage.special-programs.reports.export'] = route('manage.special-programs.reports.export', ['qiyas', 'section' => 'students']);
        foreach ($urls as $name => $url) {
            $status = $this->actingAs($super)->get($url)->getStatusCode();
            $this->assertLessThan(400, $status, "$name returned $status");
        }
    }
}
