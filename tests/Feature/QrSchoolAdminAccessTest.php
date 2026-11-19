<?php

namespace Tests\Feature;

use App\Models\JobTitle;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** School admins get the QR pages through their role; every page/write keeps its own qr.* permission (qr.view never opens devices/print). */
class QrSchoolAdminAccessTest extends TestCase
{
    private const PAGES = ['admin.qr.scanner', 'admin.qr.log', 'admin.qr.link.index', 'admin.qr.devices.index', 'admin.qr.cards.print'];

    private const SEED = '2026_11_21_100001_seed_qr_school_admin_access.php';

    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_06_14_300002_*',
            '2026_06_16_600000_*', '2026_06_16_710000_*', '2026_06_30_010000_*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function runQrSeed(): void
    {
        (require database_path('migrations/'.self::SEED))->up();
        (require database_path('migrations/2026_11_21_100002_grant_qr_pages_to_school_admin_role.php'))->up();
    }

    private function user(string $role, ?JobTitle $title = null): User
    {
        $user = User::factory()->create(['school_id' => School::factory()->create()->id, 'job_title_id' => $title?->id]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => $role], ['name' => $role]));

        return $user;
    }

    private function title(string $slug, string ...$permissions): JobTitle
    {
        $title = JobTitle::forceCreate(['name_ar' => $slug, 'name_en' => $slug, 'slug' => $slug]);
        foreach ($permissions as $p) {
            $id = Permission::firstOrCreate(['slug' => $p], ['name' => $p, 'group' => strtok($p, '.')])->id;
            DB::table('job_title_permissions')->insert(['job_title_id' => $title->id, 'permission_id' => $id, 'scope' => 'all', 'created_at' => now(), 'updated_at' => now()]);
        }

        return $title;
    }

    private function assertPages(User $user, int $status): void
    {
        foreach (self::PAGES as $route) {
            $this->actingAs($user)->get(route($route))->assertStatus($status);
        }
    }

    /** @return array<string, int> write route => status */
    private function writes(User $user): array
    {
        $this->actingAs($user);

        return [
            'scan' => $this->postJson(route('admin.qr.scan'), ['token' => 'nope'])->status(),
            'close-day' => $this->post(route('admin.qr.close-day'))->status(),
            'link.assign' => $this->post(route('admin.qr.link.assign'))->status(),
            'devices.store' => $this->post(route('admin.qr.devices.store'))->status(),
        ];
    }

    public function test_school_admin_role_opens_the_five_pages_after_the_seed(): void
    {
        $admin = $this->user('school-admin');
        $this->assertPages($admin, 403);
        $this->runQrSeed();
        $this->assertPages($admin->fresh(), 200);
        $this->assertNotSame(403, $this->writes($admin->fresh())['scan']);
        $this->actingAs($admin->fresh())->post(route('admin.qr.groups.store'))->assertForbidden();
    }

    public function test_qr_view_alone_never_opens_devices_or_print(): void
    {
        $user = $this->user('school-admin', $this->title('viewer', 'qr.view'));
        foreach (['admin.qr.devices.index', 'admin.qr.cards.print', 'admin.qr.scanner', 'admin.qr.log', 'admin.qr.link.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_teachers_and_students_stay_out(): void
    {
        $this->runQrSeed();
        foreach (['teacher', 'student'] as $role) {
            $user = $this->user($role);
            $this->assertPages($user, 403);
            $this->assertSame([403], array_values(array_unique($this->writes($user))), $role);
        }
    }
}
