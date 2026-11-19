<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class TeacherReportsTest extends TestCase
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

    /** Exam created by $owner with one closed attempt of $pct% (4 items). */
    private function exam(School $school, string $program, User $owner, User $student, int $correct, string $title = 'E'): int
    {
        $subjectId = (int) DB::table('sp_subjects')->where('program', $program)->orderBy('id')->value('id');
        $examId = DB::table('sp_exams')->insertGetId(['school_id' => $school->id, 'program' => $program, 'title' => $title, 'type' => 'single',
            'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => $owner->id, 'creator_role' => 'teacher',
            'teacher_id' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
        $es = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $examId, 'sp_subject_id' => $subjectId, 'teacher_id' => $owner->id,
            'duration_minutes' => 30, 'total_questions' => 4, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sp_attempts')->insert(['school_id' => $school->id, 'program' => $program, 'exam_id' => $examId, 'exam_subject_id' => $es,
            'student_id' => $student->id, 'status' => 'completed', 'started_at' => now()->subHour(), 'deadline_at' => now(), 'ended_at' => now(),
            'items_count' => 4, 'correct_count' => $correct, 'score_pct' => $correct * 25, 'created_at' => now(), 'updated_at' => now()]);

        return $examId;
    }

    private function assign(School $school, User $t, string $program, ?int $subject = null): void
    {
        DB::table('sp_teachers')->insert(['school_id' => $school->id, 'program' => $program, 'sp_subject_id' => $subject,
            'teacher_id' => $t->id, 'created_by' => $t->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_teacher_sees_only_his_exams_and_report(): void
    {
        $school = School::factory()->create();
        $a = $this->person('teacher', $school);
        $b = $this->person('teacher', $school);
        $student = User::factory()->create(['school_id' => $school->id]);
        $this->assign($school, $a, 'qiyas');
        $this->assign($school, $b, 'qiyas');
        $mine = $this->exam($school, 'qiyas', $a, $student, 3, 'MINE');
        $theirs = $this->exam($school, 'qiyas', $b, $student, 1, 'THEIRS');

        $this->actingAs($a)->get(route('manage.special-programs.teacher-reports.index'))
            ->assertOk()->assertSee('MINE')->assertDontSee('THEIRS');
        $this->actingAs($a)->get(route('manage.special-programs.teacher-reports.exam', $mine))
            ->assertOk()->assertSee('75');
        $this->actingAs($a)->get(route('manage.special-programs.teacher-reports.exam', $theirs))->assertNotFound();
        $this->actingAs($a)->get(route('manage.special-programs.teacher-reports.export', $theirs))->assertNotFound();
        $this->actingAs($a)->get(route('manage.special-programs.teacher-reports.export', $mine))->assertOk();
    }

    public function test_cross_school_and_unassigned_program_blocked(): void
    {
        $s1 = School::factory()->create();
        $s2 = School::factory()->create();
        $other = $this->person('teacher', $s2);
        $mine = $this->person('teacher', $s1);
        $student = User::factory()->create(['school_id' => $s2->id]);
        $this->assign($s2, $other, 'nafis');
        $exam = $this->exam($s2, 'nafis', $other, $student, 2);
        $this->assign($s1, $mine, 'qiyas');
        $this->actingAs($mine)->get(route('manage.special-programs.teacher-reports.exam', $exam))->assertNotFound();

        // owner of an exam in a program he is no longer assigned to
        DB::table('sp_teachers')->where('teacher_id', $other->id)->delete();
        $this->actingAs($other)->get(route('manage.special-programs.teacher-reports.exam', $exam))->assertForbidden();
    }

    public function test_students_cannot_open_teacher_reports(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->person('student', $school))->get(route('manage.special-programs.teacher-reports.index'))->assertForbidden();
    }
}
