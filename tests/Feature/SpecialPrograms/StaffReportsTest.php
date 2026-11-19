<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class StaffReportsTest extends TestCase
{
    use LoadsSpMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
        app()->setLocale('ar');
    }

    private function person(string $role, School $school): User
    {
        $u = User::factory()->create(['school_id' => $school->id]);
        $u->roles()->attach(Role::where('slug', $role)->first());

        return $u;
    }

    /** one closed attempt for $student in a new exam created by $creatorId */
    private function attempt(School $school, User $student, string $program = 'qiyas', int $creatorId = 1, string $role = 'admin'): int
    {
        $subjectId = (int) DB::table('sp_subjects')->where('program', $program)->orderBy('id')->value('id');
        $examId = DB::table('sp_exams')->insertGetId(['school_id' => $school->id, 'program' => $program, 'title' => 'اختبار تجريبي', 'type' => 'single',
            'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => $creatorId, 'creator_role' => $role,
            'teacher_id' => $role === 'teacher' ? $creatorId : null, 'created_at' => now(), 'updated_at' => now()]);
        $esId = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $examId, 'sp_subject_id' => $subjectId, 'duration_minutes' => 30,
            'total_questions' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $skill = DB::table('sp_skills')->insertGetId(['sp_subject_id' => $subjectId, 'name' => 'مهارة', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('sp_attempts')->insertGetId(['school_id' => $school->id, 'program' => $program, 'exam_id' => $examId, 'exam_subject_id' => $esId,
            'student_id' => $student->id, 'status' => 'completed', 'started_at' => now()->subHour(), 'deadline_at' => now(), 'ended_at' => now(),
            'items_count' => 2, 'correct_count' => 1, 'score_pct' => 50, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([true, false] as $n => $ok) {
            DB::table('sp_attempt_items')->insert(['attempt_id' => $id, 'sp_skill_id' => $skill, 'difficulty' => 'easy', 'order_index' => $n,
                'options' => '[]', 'correct_index' => 0, 'answer_index' => $ok ? 0 : 1, 'is_correct' => $ok]);
        }

        return $id;
    }

    public function test_page_renders_for_admin_and_super_admin_without_school(): void
    {
        $school = School::factory()->create();
        $student = $this->person('student', $school);
        $this->attempt($school, $student);
        foreach (['qiyas', 'tahsili', 'nafis'] as $p) {
            $this->actingAs($this->person('school-admin', $school))->get(route('manage.special-programs.reports.index', $p))->assertOk();
        }
        $this->actingAs($student->fresh())->get(route('manage.special-programs.reports.index', 'qiyas'))->assertForbidden();

        $this->actingAs($this->createSuperAdmin())->withSession(['scope.school_id' => null]);
        $this->get(route('manage.special-programs.reports.index', 'qiyas'))->assertOk()->assertSee('alert-warning', false);
        $this->get(route('manage.special-programs.reports.print', 'qiyas'))->assertOk();
    }

    public function test_excel_and_pdf_exports_return_right_content_type(): void
    {
        $school = School::factory()->create();
        $student = $this->person('student', $school);
        $this->attempt($school, $student);
        $this->actingAs($this->person('school-admin', $school));

        foreach (['students', 'subjects', 'skills', 'exams', 'distribution', 'classes'] as $s) {
            $r = $this->get(route('manage.special-programs.reports.export', ['qiyas', 'section' => $s]))->assertOk();
            $this->assertStringContainsString('spreadsheetml', $r->headers->get('content-type'));
        }
        $this->get(route('manage.special-programs.reports.export', ['tahsili', 'section' => 'difficulty']))->assertOk();
        $this->get(route('manage.special-programs.reports.export', ['qiyas', 'section' => 'difficulty']))->assertNotFound();
        $this->get(route('manage.special-programs.reports.export', ['qiyas', 'section' => 'nope']))->assertNotFound();
        $this->get(route('manage.special-programs.reports.print', 'qiyas'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_student_report_is_school_scoped_and_teacher_only_sees_his_students(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $mine = $this->person('student', $school);
        $foreign = $this->person('student', $other);
        $teacher = $this->person('teacher', $school);
        $theirs = $this->person('student', $school);
        $this->attempt($school, $mine, 'qiyas', $teacher->id, 'teacher');
        $this->attempt($school, $theirs, 'qiyas', 999, 'admin');
        $this->attempt($other, $foreign);

        $this->actingAs($this->person('school-admin', $school));
        $this->get(route('manage.special-programs.student-report', ['qiyas', $mine->id]))->assertOk()->assertSee($mine->name);
        $this->get(route('manage.special-programs.student-report.pdf', ['qiyas', $mine->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('manage.special-programs.student-report', ['qiyas', $foreign->id]))->assertNotFound();

        $this->actingAs($teacher);
        $this->get(route('manage.special-programs.student-report', ['qiyas', $mine->id]))->assertOk();
        $this->get(route('manage.special-programs.student-report', ['qiyas', $theirs->id]))->assertNotFound();
        $this->get(route('manage.special-programs.reports.index', 'qiyas'))->assertOk()->assertDontSee($theirs->name);
    }
}
