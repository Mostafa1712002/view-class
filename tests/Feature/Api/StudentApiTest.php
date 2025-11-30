<?php

namespace Tests\Feature\Api;

use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    private function createStudentWithData(): array
    {
        $school = School::factory()->create();
        $section = Section::factory()->create(['school_id' => $school->id]);
        $academicYear = \App\Models\AcademicYear::factory()->create(['school_id' => $school->id]);
        $classRoom = ClassRoom::factory()->create([
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $student = $this->createStudent($school);
        $student->update(['class_room_id' => $classRoom->id]);

        return [
            'school' => $school,
            'student' => $student,
            'classRoom' => $classRoom,
            'academicYear' => $academicYear,
        ];
    }

    public function test_student_can_access_dashboard(): void
    {
        $data = $this->createStudentWithData();
        Sanctum::actingAs($data['student']);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_non_student_cannot_access_student_dashboard(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        Sanctum::actingAs($teacher);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertStatus(403);
    }

    public function test_student_can_view_grades(): void
    {
        $data = $this->createStudentWithData();
        Sanctum::actingAs($data['student']);

        $response = $this->getJson('/api/student/grades');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_student_can_view_attendance(): void
    {
        $data = $this->createStudentWithData();
        Sanctum::actingAs($data['student']);

        $response = $this->getJson('/api/student/attendance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_student_can_view_schedule(): void
    {
        $data = $this->createStudentWithData();
        Sanctum::actingAs($data['student']);

        $response = $this->getJson('/api/student/schedule');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_student_can_view_weekly_plans(): void
    {
        $data = $this->createStudentWithData();
        Sanctum::actingAs($data['student']);

        $response = $this->getJson('/api/student/weekly-plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_student_api(): void
    {
        $response = $this->getJson('/api/student/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/student/grades');
        $response->assertStatus(401);

        $response = $this->getJson('/api/student/attendance');
        $response->assertStatus(401);
    }
}
