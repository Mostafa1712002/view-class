<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_admin_can_view_users_list(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);
        User::factory()->count(5)->create(['school_id' => $school->id]);

        $response = $this->actingAs($user)->get('/manage/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_user(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);

        $response = $this->actingAs($user)->get('/manage/users/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_user(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);
        $role = Role::where('slug', 'teacher')->first();

        $response = $this->actingAs($user)->post('/manage/users', [
            'name' => 'معلم جديد',
            'email' => 'newteacher@school.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '0501234567',
            'gender' => 'male',
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newteacher@school.com']);
    }

    public function test_admin_can_delete_user(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $targetUser = User::factory()->create(['school_id' => $school->id]);

        $response = $this->actingAs($admin)->delete("/manage/users/{$targetUser->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_user_validation_requires_name(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $role = Role::where('slug', 'teacher')->first();

        $response = $this->actingAs($admin)->post('/manage/users', [
            'email' => 'test@school.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->id,
        ]);

        $response->assertSessionHasErrors('name');
    }
}
