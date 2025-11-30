<?php

namespace Tests;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function createSuperAdmin(): User
    {
        $user = User::factory()->create([
            'email' => 'superadmin@test.com',
        ]);
        $role = Role::where('slug', 'super-admin')->first();
        $user->roles()->attach($role);
        return $user;
    }

    protected function createSchoolAdmin(?School $school = null): User
    {
        $school = $school ?? School::factory()->create();
        $user = User::factory()->create([
            'email' => 'schooladmin@test.com',
            'school_id' => $school->id,
        ]);
        $role = Role::where('slug', 'school-admin')->first();
        $user->roles()->attach($role);
        return $user;
    }

    protected function createTeacher(?School $school = null): User
    {
        $school = $school ?? School::factory()->create();
        $user = User::factory()->create([
            'email' => 'teacher@test.com',
            'school_id' => $school->id,
        ]);
        $role = Role::where('slug', 'teacher')->first();
        $user->roles()->attach($role);
        return $user;
    }

    protected function createStudent(?School $school = null): User
    {
        $school = $school ?? School::factory()->create();
        $user = User::factory()->create([
            'email' => 'student@test.com',
            'school_id' => $school->id,
        ]);
        $role = Role::where('slug', 'student')->first();
        $user->roles()->attach($role);
        return $user;
    }

    protected function createParent(?School $school = null): User
    {
        $school = $school ?? School::factory()->create();
        $user = User::factory()->create([
            'email' => 'parent@test.com',
            'school_id' => $school->id,
        ]);
        $role = Role::where('slug', 'parent')->first();
        $user->roles()->attach($role);
        return $user;
    }
}
