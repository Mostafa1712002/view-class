<?php

namespace Tests\Feature;

use App\Models\{ClassRoom, DiscussionRoom, Exam, GradeReport, School, StudentExam, User, VirtualClass};
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentDistanceLearningTest extends TestCase
{
    public function refreshDatabase(): void
    {
        // The full history contains MySQL-only ALTERs; load this feature's real migrations in SQLite only.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $patterns = [
            '0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_04_29_11000*', '2026_05_18_120067_*', '2026_05_25_13000*',
            '2026_06_12_235000_*', '2026_06_13_000100_*',
            '2026_06_15_500000_extend_virtual*', '2026_07_11_120000_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_07_11_130000_*',
        ];
        $paths = [];
        foreach ($patterns as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function examFixture(): array
    {
        $class = ClassRoom::factory()->create();
        $school = $class->section->school;
        $teacher = $this->createTeacher($school);
        $student = $this->createStudent($school);
        $exam = Exam::factory()->create(['class_id' => $class->id, 'academic_year_id' => $class->academic_year_id,
            'teacher_id' => $teacher->id, 'total_marks' => 80]);
        $attempt = StudentExam::create(['exam_id' => $exam->id, 'student_id' => $student->id,
            'status' => 'graded', 'score' => 63, 'attempt_number' => 1]);
        $report = GradeReport::create(['school_id' => $school->id, 'class_id' => $class->id, 'title' => 'Report']);
        $column = $report->columns()->create(['title' => 'Exam', 'type' => 'numeric', 'max_score' => 30]);
        return [$exam, $attempt, $report, $column, $teacher];
    }

    public function test_push_scales_scores_is_idempotent_and_excludes_ungraded_attempts(): void
    {
        [$exam, $attempt, $report, $column] = $this->examFixture();
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);
        $pending = StudentExam::create(['exam_id' => $exam->id, 'student_id' => User::factory()->create()->id,
            'status' => 'submitted', 'score' => 20]);
        $payload = ['grade_report_id' => $report->id, 'grade_report_column_id' => $column->id];
        $this->get(route('admin.exams.results', $exam))->assertOk()->assertSee(__('exam_transfer.title'));
        $url = route('admin.exams.push-to-grade-report', $exam);
        $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_grade_values', ['grade_report_id' => $report->id,
            'grade_report_column_id' => $column->id, 'student_id' => $attempt->student_id,
            'score' => 23.63, 'recorded_by' => $admin->id]);
        StudentExam::create(['exam_id' => $exam->id, 'student_id' => $attempt->student_id,
            'status' => 'graded', 'score' => 40, 'attempt_number' => 2]);
        $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('student_grade_values', 1);
        $this->assertDatabaseHas('student_grade_values', ['student_id' => $attempt->student_id, 'score' => 15]);
        $this->assertDatabaseMissing('student_grade_values', ['student_id' => $pending->student_id]);
    }

    public function test_push_rejects_cross_school_reports_wrong_columns_and_unassigned_teacher(): void
    {
        [$exam, $attempt, $report, $column, $teacher] = $this->examFixture();
        $this->actingAs($this->createSuperAdmin());
        $other = GradeReport::create(['school_id' => School::factory()->create()->id, 'title' => 'Other']);
        $otherColumn = $other->columns()->create(['title' => 'Other', 'type' => 'numeric']);
        $url = route('admin.exams.push-to-grade-report', $exam);
        $this->post($url, ['grade_report_id' => $other->id, 'grade_report_column_id' => $otherColumn->id])->assertNotFound();
        $this->post($url, ['grade_report_id' => $report->id, 'grade_report_column_id' => $otherColumn->id])->assertNotFound();
        $this->actingAs($teacher);
        $url = route('teacher.exams.push-to-grade-report', $exam);
        $payload = ['grade_report_id' => $report->id, 'grade_report_column_id' => $column->id];
        $this->post($url, $payload)->assertNotFound();
        DB::table('class_teacher')->insert(['teacher_id' => $teacher->id, 'class_id' => $exam->class_id]);
        $this->get(route('teacher.exams.results', $exam))->assertOk();
        $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('student_grade_values', ['recorded_by' => $teacher->id]);
    }

    public function test_school_admin_cannot_transfer_or_view_another_schools_exam(): void
    {
        [$exam, , $report, $column] = $this->examFixture();
        $this->actingAs($this->createSchoolAdmin());
        $this->get(route('admin.exams.results', $exam))->assertNotFound();
        $this->post(route('admin.exams.push-to-grade-report', $exam), [
            'grade_report_id' => $report->id, 'grade_report_column_id' => $column->id,
        ])->assertNotFound();
        $this->assertDatabaseCount('student_grade_values', 0);
    }

    public function test_push_rejects_locked_reports_and_zero_exam_total(): void
    {
        [$exam, , $report, $column] = $this->examFixture();
        $this->actingAs($this->createSuperAdmin());
        $payload = ['grade_report_id' => $report->id, 'grade_report_column_id' => $column->id];
        $report->update(['is_locked' => true]);
        $this->post(route('admin.exams.push-to-grade-report', $exam), $payload)->assertSessionHasErrors('grade_report_id');
        $report->update(['is_locked' => false]);
        $exam->update(['total_marks' => 0]);
        $this->post(route('admin.exams.push-to-grade-report', $exam), $payload)->assertSessionHasErrors('grade_report_column_id');
        $this->assertDatabaseCount('student_grade_values', 0);
    }

    public function test_hub_renders_for_null_school_super_admin_and_scopes_school_admin_with_attendance(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $class = ClassRoom::factory()->create();
        $students = User::factory()->count(2)->create(['school_id' => $school->id]);
        $class->students()->attach($students->pluck('id'));
        $held = VirtualClass::create(['school_id' => $school->id, 'teacher_id' => $admin->id,
            'created_by' => $admin->id, 'class_id' => $class->id, 'title' => 'Held session',
            'scheduled_at' => now()->subDay(), 'status' => 'ended']);
        $held->attendees()->create(['student_id' => $students[0]->id, 'school_id' => $school->id,
            'attendance_status' => 'present', 'joined_at' => now()->subDay()]);
        VirtualClass::create(['school_id' => School::factory()->create()->id, 'teacher_id' => $admin->id,
            'created_by' => $admin->id, 'title' => 'Other school session', 'scheduled_at' => now()->addDay()]);
        DiscussionRoom::create(['school_id' => $school->id, 'created_by' => $admin->id, 'title' => 'Room', 'status' => 'active']);
        $super = $this->createSuperAdmin();
        $super->update(['school_id' => null]);
        $this->actingAs($super)->get(route('manage.distance-learning.index'))
            ->assertOk()->assertSee('Other school session')->assertSee('50%');
        $this->actingAs($admin)->get(route('manage.distance-learning.index'))
            ->assertOk()->assertSee('Held session')->assertSee('50%')->assertDontSee('Other school session')
            ->assertViewHas('counts', fn ($counts) => $counts['total'] === 1 && $counts['rooms'] === 1);
    }
}
