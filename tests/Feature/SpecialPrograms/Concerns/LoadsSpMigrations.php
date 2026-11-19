<?php

namespace Tests\Feature\SpecialPrograms\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Boots sqlite :memory: with a trimmed migration chain (the full chain has MySQL-only ALTERs).
 * 368002 is skipped: it needs bank_questions, which no special-programs test loads.
 * TestCase::setUp seeds RoleSeeder after migrating; call grantSpPermissions() right after parent::setUp().
 */
trait LoadsSpMigrations
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*',
            '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_07_25_100000_*', '2026_11_16_372*', '2026_11_19_368*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        $paths = array_filter($paths, fn ($p) => ! str_contains($p, '368002'));
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    /** Re-grants special_programs.* to school-admin/teacher (RoleSeeder's sync() may have wiped them). */
    protected function grantSpPermissions(): void
    {
        $grants = ['school-admin' => ['view', 'manage', 'exams', 'reports', 'settings'], 'teacher' => ['view', 'exams', 'reports']];
        foreach ($grants as $role => $actions) {
            $roleId = DB::table('roles')->where('slug', $role)->value('id');
            foreach ($actions as $action) {
                $permId = DB::table('permissions')->where('slug', "special_programs.$action")->value('id');
                DB::table('permission_role')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permId],
                    ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
