<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_default_roles_exist(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => 'super-admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'school-admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'teacher']);
        $this->assertDatabaseHas('roles', ['slug' => 'student']);
        $this->assertDatabaseHas('roles', ['slug' => 'parent']);
    }

    public function test_role_has_many_users(): void
    {
        $role = Role::where('slug', 'teacher')->first();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $user->roles()->attach($role);
        }

        $this->assertCount(3, $role->users);
    }

    public function test_role_can_have_permissions(): void
    {
        $role = Role::where('slug', 'teacher')->first();

        $permission = Permission::create([
            'name' => 'إدارة الطلاب',
            'slug' => 'manage-students',
        ]);

        $role->permissions()->attach($permission);

        $this->assertTrue($role->permissions->contains($permission));
    }

    public function test_role_has_permission_method(): void
    {
        $role = Role::where('slug', 'teacher')->first();

        $permission = Permission::create([
            'name' => 'إدارة الدرجات',
            'slug' => 'manage-grades',
        ]);

        $role->permissions()->attach($permission);

        $this->assertTrue($role->hasPermission('manage-grades'));
        $this->assertFalse($role->hasPermission('manage-schools'));
    }
}
