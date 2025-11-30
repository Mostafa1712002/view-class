<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    public function test_super_admin_can_view_schools_list(): void
    {
        $user = $this->createSuperAdmin();
        School::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/admin/schools');

        $response->assertStatus(200);
    }

    public function test_super_admin_can_create_school(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->get('/admin/schools/create');
        $response->assertStatus(200);
    }

    public function test_super_admin_can_store_school(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->post('/admin/schools', [
            'name' => 'مدرسة جديدة',
            'code' => 'NEW001',
            'email' => 'new@school.com',
            'phone' => '0501234567',
            'address' => 'عنوان المدرسة',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schools', ['name' => 'مدرسة جديدة']);
    }

    public function test_super_admin_can_delete_school(): void
    {
        $user = $this->createSuperAdmin();
        $school = School::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/schools/{$school->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    public function test_school_admin_cannot_view_schools_list(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);

        $response = $this->actingAs($user)->get('/admin/schools');
        $response->assertStatus(403);
    }

    public function test_school_validation_requires_name(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->post('/admin/schools', [
            'email' => 'test@school.com',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
