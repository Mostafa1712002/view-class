<?php

namespace Tests\Unit\Models;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    public function test_attendance_can_be_created(): void
    {
        $school = School::factory()->create();
        $section = Section::factory()->create(['school_id' => $school->id]);
        $academicYear = \App\Models\AcademicYear::factory()->create(['school_id' => $school->id]);
        $classroom = ClassRoom::factory()->create([
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $student = $this->createStudent($school);
        $teacher = $this->createTeacher($school);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
            'date' => Carbon::today(),
            'period' => 1,
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'present',
        ]);
    }

    public function test_attendance_belongs_to_student(): void
    {
        $attendance = Attendance::factory()->create();
        $this->assertInstanceOf(User::class, $attendance->student);
    }

    public function test_attendance_belongs_to_teacher(): void
    {
        $attendance = Attendance::factory()->create();
        $this->assertInstanceOf(User::class, $attendance->teacher);
    }

    public function test_attendance_belongs_to_classroom(): void
    {
        $attendance = Attendance::factory()->create();
        $this->assertInstanceOf(ClassRoom::class, $attendance->classRoom);
    }

    public function test_attendance_status_types(): void
    {
        $statuses = ['present', 'absent', 'late', 'excused'];

        foreach ($statuses as $status) {
            $attendance = Attendance::factory()->create(['status' => $status]);
            $this->assertEquals($status, $attendance->status);
        }
    }

    public function test_attendance_date_is_carbon_instance(): void
    {
        $attendance = Attendance::factory()->create();
        $this->assertInstanceOf(Carbon::class, $attendance->date);
    }

    public function test_present_scope(): void
    {
        Attendance::factory()->present()->count(5)->create();
        Attendance::factory()->absent()->count(3)->create();

        $this->assertEquals(5, Attendance::present()->count());
    }

    public function test_attendance_status_helpers(): void
    {
        $present = Attendance::factory()->create(['status' => 'present']);
        $absent = Attendance::factory()->create(['status' => 'absent']);
        $late = Attendance::factory()->create(['status' => 'late']);
        $excused = Attendance::factory()->create(['status' => 'excused']);

        $this->assertTrue($present->isPresent());
        $this->assertTrue($absent->isAbsent());
        $this->assertTrue($late->isLate());
        $this->assertTrue($excused->isExcused());
    }

    public function test_attendance_status_label(): void
    {
        $present = Attendance::factory()->create(['status' => 'present']);
        $absent = Attendance::factory()->create(['status' => 'absent']);

        $this->assertEquals('حاضر', $present->status_label);
        $this->assertEquals('غائب', $absent->status_label);
    }
}
