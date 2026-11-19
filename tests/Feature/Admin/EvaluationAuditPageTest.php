<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Tests\TestCase;

/** /admin/evaluations/audit used to 500: its view failed to compile. */
class EvaluationAuditPageTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    public function test_audit_page_renders_with_log_rows(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'school-admin'], ['name' => 'school-admin']));
        ActivityLog::create(['user_id' => $admin->id, 'school_id' => $school->id, 'action' => 'evaluation.form.updated',
            'description' => 'audit-row-marker', 'old_values' => ['title' => 'a'], 'new_values' => ['title' => 'b']]);

        $this->actingAs($admin)->get(route('admin.eval-audit.index'))
            ->assertOk()->assertSee('audit-row-marker')->assertSee('school-admin');
    }
}
