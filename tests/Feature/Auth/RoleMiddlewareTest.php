<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_super_admin_can_access_admin_routes(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->get('/admin/schools');
        $response->assertStatus(200);
    }

    public function test_school_admin_can_access_manage_routes(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);

        $response = $this->actingAs($user)->get('/manage/users');
        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_admin_school_routes(): void
    {
        $school = School::factory()->create();
        $user = $this->createTeacher($school);

        $response = $this->actingAs($user)->get('/admin/schools');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $school = School::factory()->create();
        $user = $this->createStudent($school);

        $response = $this->actingAs($user)->get('/admin/schools');
        $response->assertStatus(403);
    }

    public function test_student_cannot_access_manage_routes(): void
    {
        $school = School::factory()->create();
        $user = $this->createStudent($school);

        $response = $this->actingAs($user)->get('/manage/users');
        $response->assertStatus(403);
    }
}
