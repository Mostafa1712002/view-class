<?php

namespace Tests\Unit\Models;

use App\Models\Grade;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Tests\TestCase;

class GradeTest extends TestCase
{
    public function test_grade_can_be_created(): void
    {
        $school = School::factory()->create();
        $section = \App\Models\Section::factory()->create(['school_id' => $school->id]);
        $academicYear = \App\Models\AcademicYear::factory()->create(['school_id' => $school->id]);
        $classroom = \App\Models\ClassRoom::factory()->create([
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $student = $this->createStudent($school);
        $teacher = $this->createTeacher($school);
        $subject = Subject::factory()->create(['school_id' => $school->id]);

        $grade = Grade::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'class_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'semester' => 'first',
            'quiz_avg' => 85,
            'homework_avg' => 90,
            'midterm' => 80,
            'final' => 85,
            'participation' => 95,
            'total' => 85,
            'letter_grade' => 'B+',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'total' => 85,
        ]);
    }

    public function test_grade_belongs_to_student(): void
    {
        $grade = Grade::factory()->create();
        $this->assertInstanceOf(User::class, $grade->student);
    }

    public function test_grade_belongs_to_subject(): void
    {
        $grade = Grade::factory()->create();
        $this->assertInstanceOf(Subject::class, $grade->subject);
    }

    public function test_grade_belongs_to_teacher(): void
    {
        $grade = Grade::factory()->create();
        $this->assertInstanceOf(User::class, $grade->teacher);
    }

    public function test_grade_letter_calculation(): void
    {
        $grade = new Grade();

        $this->assertEquals('A+', $grade->calculateLetterGrade(95));
        $this->assertEquals('A', $grade->calculateLetterGrade(92));
        $this->assertEquals('B+', $grade->calculateLetterGrade(87));
        $this->assertEquals('B', $grade->calculateLetterGrade(82));
        $this->assertEquals('C+', $grade->calculateLetterGrade(77));
        $this->assertEquals('C', $grade->calculateLetterGrade(72));
        $this->assertEquals('D+', $grade->calculateLetterGrade(67));
        $this->assertEquals('D', $grade->calculateLetterGrade(62));
        $this->assertEquals('F', $grade->calculateLetterGrade(50));
    }

    public function test_grade_is_passing(): void
    {
        $passingGrade = Grade::factory()->create(['total' => 75]);
        $failingGrade = Grade::factory()->create(['total' => 55]);

        $this->assertTrue($passingGrade->isPassing());
        $this->assertFalse($failingGrade->isPassing());
    }

    public function test_grade_semester_label(): void
    {
        $gradeFirst = Grade::factory()->create(['semester' => 'first']);
        $gradeSecond = Grade::factory()->create(['semester' => 'second']);

        $this->assertEquals('الفصل الأول', $gradeFirst->semester_label);
        $this->assertEquals('الفصل الثاني', $gradeSecond->semester_label);
    }

    public function test_published_scope(): void
    {
        Grade::factory()->count(3)->create(['is_published' => true]);
        Grade::factory()->count(2)->create(['is_published' => false]);

        $this->assertEquals(3, Grade::published()->count());
    }
}
