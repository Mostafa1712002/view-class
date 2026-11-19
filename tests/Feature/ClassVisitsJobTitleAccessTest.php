<?php

namespace Tests\Feature;

use App\Models\JobTitle;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Class visits follow the job-title matrix (class_visits.view), not "any school admin". */
class ClassVisitsJobTitleAccessTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_06_14_300002_*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function admin(?JobTitle $title): User
    {
        $role = Role::firstOrCreate(['slug' => 'school-admin'], ['name' => 'school-admin']);
        $user = User::factory()->create(['school_id' => School::factory()->create()->id, 'job_title_id' => $title?->id]);
        $user->roles()->attach($role);

        return $user;
    }

    private function configuredTitle(string $slug): JobTitle
    {
        $title = JobTitle::forceCreate(['name_ar' => $slug, 'name_en' => $slug, 'slug' => $slug]);
        $other = Permission::create(['name' => 'x', 'slug' => 'students.view', 'group' => 'students']);
        DB::table('job_title_permissions')->insert(['job_title_id' => $title->id, 'permission_id' => $other->id, 'scope' => 'all', 'created_at' => now(), 'updated_at' => now()]);

        return $title;
    }


    public function test_configured_job_title_without_permission_is_denied_and_with_permission_allowed(): void
    {
        $title = $this->configuredTitle('hod');
        $this->actingAs($this->admin($title))->get(route('admin.class-visits.index'))->assertForbidden();

        $perm = Permission::create(['name' => 'v', 'slug' => 'class_visits.view', 'group' => 'class_visits']);
        DB::table('job_title_permissions')->insert(['job_title_id' => $title->id, 'permission_id' => $perm->id, 'scope' => 'all', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->admin($title))->get(route('admin.class-visits.index'))->assertStatus(500); // past the gate; list tables are not in this slim schema
    }

    public function test_admin_without_configured_job_title_keeps_access(): void
    {
        $this->actingAs($this->admin(null))->get(route('admin.class-visits.index'))->assertStatus(500); // past the gate; list tables are not in this slim schema
    }

    public function test_seed_migration_grants_view_to_configured_titles(): void
    {
        $title = $this->configuredTitle('vice');
        (require database_path('migrations/2026_11_21_100000_seed_class_visits_job_title_access.php'))->up();
        $this->assertTrue($title->fresh()->hasPermission('class_visits.view'));
    }
}
