<?php

namespace Tests\Feature;

use App\Models\School;
use Tests\TestCase;

class PointsRoutesAccessTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_04_150000_*', '2026_06_14_300000_*', '2026_04_28_100001_*', '2026_05_18_120061_*', '2026_05_25_120000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    public function test_only_staff_reach_manage_routes_and_only_super_admin_edits_platform_rules(): void
    {
        // roles are seeded after the migrations in setUp(), so re-run the grant
        (require database_path('migrations/2026_11_19_371001_seed_points_permissions.php'))->up();
        $school = School::factory()->create();
        foreach (['createTeacher', 'createStudent', 'createParent'] as $make) {
            $this->actingAs($this->$make($school));
            foreach (['manage.points.index', 'manage.points.rules', 'manage.points.periods.index', 'manage.points.honour'] as $name) {
                $this->get(route($name))->assertForbidden();
            }
            $this->put(route('manage.points.rules.platform'))->assertForbidden();
        }

        // school admin passes the gate, but not platform rules
        $this->actingAs($this->createSchoolAdmin($school));
        $this->get(route('manage.points.index'))->assertOk();
        $this->get(route('manage.points.rules'))->assertOk();
        $this->put(route('manage.points.rules.platform'))->assertForbidden();

        $this->actingAs($this->createSuperAdmin());
        $this->put(route('manage.points.rules.platform'))->assertRedirect();
    }

    public function test_my_points_is_open_to_any_signed_in_user_but_not_guests(): void
    {
        $this->get(route('my.points.index'))->assertRedirect();
        $this->actingAs($this->createStudent(School::factory()->create()));
        $this->get(route('my.points.index'))->assertOk();
    }
}
