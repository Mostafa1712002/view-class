<?php

namespace Tests\Unit\Models;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_belongs_to_school(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);

        $this->assertInstanceOf(School::class, $user->school);
        $this->assertEquals($school->id, $user->school->id);
    }

    public function test_user_can_have_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'teacher')->first();
        $user->roles()->attach($role);

        $this->assertTrue($user->roles->contains($role));
    }

    public function test_user_has_role_method(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'teacher')->first();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('teacher'));
        $this->assertFalse($user->hasRole('student'));
    }

    public function test_user_has_any_role_method(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'teacher')->first();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasAnyRole(['teacher', 'student']));
        $this->assertFalse($user->hasAnyRole(['student', 'parent']));
    }

    public function test_user_is_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_user_is_school_admin(): void
    {
        $user = $this->createSchoolAdmin();
        $this->assertTrue($user->isSchoolAdmin());
    }

    public function test_user_is_teacher(): void
    {
        $user = $this->createTeacher();
        $this->assertTrue($user->isTeacher());
    }

    public function test_user_is_student(): void
    {
        $user = $this->createStudent();
        $this->assertTrue($user->isStudent());
    }

    public function test_user_is_parent(): void
    {
        $user = $this->createParent();
        $this->assertTrue($user->isParent());
    }

    public function test_user_role_name_attribute(): void
    {
        $user = $this->createTeacher();
        $this->assertEquals('معلم', $user->role_name);
    }

    public function test_user_can_assign_role(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'student')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('student'));
    }

    public function test_user_can_remove_role(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'student')->first();
        $user->assignRole($role);

        $user->removeRole($role);

        $this->assertFalse($user->hasRole('student'));
    }

    public function test_user_belongs_to_classroom(): void
    {
        $school = School::factory()->create();
        $section = \App\Models\Section::factory()->create(['school_id' => $school->id]);
        $classroom = ClassRoom::factory()->create(['section_id' => $section->id]);
        $user = User::factory()->create(['class_room_id' => $classroom->id]);

        $this->assertInstanceOf(ClassRoom::class, $user->classRoom);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(\Hash::check('password123', $user->password));
    }
}
