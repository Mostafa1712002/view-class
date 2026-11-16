<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use Illuminate\Routing\Route;
use Tests\TestCase;

class RbacSuperAdminRoutesTest extends TestCase
{
    public function refreshDatabase(): void
    {
        // The full migration history includes MySQL-only ALTER ... MODIFY.
        // Use the real RBAC schema in a fresh SQLite connection, without changing
        // RefreshDatabase's shared migration state for other test classes.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2025_11_30_163212_create_roles_table.php',
            '2025_11_30_163250_create_schools_table.php',
            '2025_11_30_163251_create_sections_table.php',
            '2025_11_30_163321_add_fields_to_users_table.php',
            '2025_11_30_202105_add_settings_to_schools_table.php',
            '2026_04_21_100002_add_multitenant_fields_to_users_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_non_super_admin_roles_cannot_access_super_admin_get_routes(): void
    {
        $router = app('router');
        $routes = collect($router->getRoutes()->getRoutes())->filter(
            fn (Route $route) => in_array('GET', $route->methods(), true)
                && in_array(CheckRole::class.':super-admin', $router->gatherRouteMiddleware($route), true)
        );
        $this->assertNotEmpty($routes, 'No super-admin GET routes were discovered.');
        fwrite(STDOUT, "\nChecking {$routes->count()} super-admin GET routes against 4 roles.\n");

        $school = School::factory()->create();
        $users = [
            'school-admin' => $this->createSchoolAdmin($school),
            'teacher' => $this->createTeacher($school),
            'student' => $this->createStudent($school),
            'parent' => $this->createParent($school),
        ];
        foreach ($users as $role => $user) {
            $this->actingAs($user);
            $this->assertAuthenticatedAs($user);
            foreach ($routes as $route) {
                // This route's provider enum rejects "1" before middleware runs.
                $uri = $route->getName() === 'admin.sms-services.senders.template'
                    ? str_replace('{provider}', 'stc', $route->uri())
                    : $route->uri();
                $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $uri);
                $response = $this->get($uri);
                $this->assertSame(403, $response->status(), "{$role}: GET {$uri} ({$route->getName()})");
            }
        }
    }

    public function test_school_admin_with_role_permissions_cannot_edit_global_roles(): void
    {
        $user = $this->createSchoolAdmin();
        $schoolAdmin = Role::where('slug', 'school-admin')->firstOrFail();
        foreach (['roles.view', 'roles.edit'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'roles']);
            $schoolAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $this->assertTrue($user->canDo('roles.view'));
        $this->assertTrue($user->canDo('roles.edit'));
        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();

        foreach (Role::all() as $role) {
            $before = $role->permissions()->orderBy('permissions.id')->pluck('permissions.id')->all();
            $this->get(route('admin.roles.permissions.edit', $role))->assertForbidden();
            $url = route('admin.roles.permissions.update', $role);
            $this->put($url, ['permissions' => ['roles.view', 'roles.edit']])->assertForbidden();
            $this->post($url, ['_method' => 'PUT', 'permissions' => []])->assertForbidden();
            $this->assertSame(
                $before,
                $role->permissions()->orderBy('permissions.id')->pluck('permissions.id')->all(),
                "Permissions changed for global role {$role->slug}."
            );
        }
    }
}
