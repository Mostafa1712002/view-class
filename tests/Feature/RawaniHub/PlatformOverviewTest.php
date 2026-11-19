<?php

namespace Tests\Feature\RawaniHub;

use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Repositories\EloquentPackageRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use Illuminate\Support\Facades\Route;
use Tests\Support\FakePlatform;
use Tests\TestCase;

class PlatformOverviewTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_20_100000_create_hub_tables', '2026_11_20_1001*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(PackageRepository::class, EloquentPackageRepository::class);
        $this->app->bind(PlatformRepository::class, EloquentPlatformRepository::class);
        $this->app->bind(PlatformAccountRepository::class, EloquentPlatformAccountRepository::class);
        $this->app->bind(SubscriptionRepository::class, EloquentSubscriptionRepository::class);
        foreach (['super-admin', 'school-admin', 'student', 'parent'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        }
        config(['rawani_hub.default_school_id' => \DB::table('schools')->insertGetId(['name' => 's', 'code' => 'x', 'created_at' => now(), 'updated_at' => now()])]);
        Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/web.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }

    private function as(string $role, ?int $school = null): User
    {
        $u = User::factory()->create(['school_id' => $school]);
        $u->roles()->attach(Role::where('slug', $role)->first());
        $this->actingAs($u);

        return $u;
    }

    private function fixture(): array
    {
        $mk = fn ($slug) => HubPlatform::create(['slug' => $slug, 'name_ar' => 'منصة '.$slug, 'base_url' => FakePlatform::BASE,
            'products' => [['code' => 'reading', 'name_ar' => 'القراءة']], 'capabilities' => [], 'is_enabled' => true]);
        $ana = $mk('anawlqdrat');
        $aafaq = $mk('aafaq');
        $pkg = HubPackage::create(['name_ar' => 'باقة أنا', 'price' => '115.00', 'currency' => 'SAR', 'duration_days' => 30, 'is_active' => true]);
        $pkg->items()->create(['platform_id' => $ana->id, 'product_code' => 'reading']);
        $other = \DB::table('schools')->insertGetId(['name' => 's2', 'code' => 'y', 'created_at' => now(), 'updated_at' => now()]);
        $mine = (int) config('rawani_hub.default_school_id');
        $a = User::factory()->create(['name' => 'طالب داخلي', 'school_id' => $mine]);
        $b = User::factory()->create(['name' => 'طالب خارجي', 'school_id' => $other]);
        $dad = User::factory()->create(['name' => 'أب داخلي']);
        $a->parents()->attach($dad->id);
        foreach ([$a, $b] as $u) {
            HubSubscription::create(['user_id' => $u->id, 'package_id' => $pkg->id, 'items' => [], 'price' => 115, 'source' => 'grant', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);
        }
        HubPlatformAccount::create(['platform_id' => $ana->id, 'role' => 'student', 'user_id' => $a->id, 'status' => 'active', 'name' => 'حساب مرتبط', 'external_id' => '1']);
        HubPlatformAccount::create(['platform_id' => $aafaq->id, 'role' => 'student', 'status' => 'unlinked', 'name' => 'حساب حر', 'external_id' => '2', 'national_id' => '1234567890']);

        return [$ana, $aafaq, $mine];
    }

    public function test_super_admin_renders_every_tab_for_both_platforms(): void
    {
        [$ana, $aafaq] = $this->fixture();
        $this->as('super-admin');
        foreach ([$ana, $aafaq] as $p) {
            foreach (['packages', 'subscribers', 'students'] as $tab) {
                $this->get(route('admin.hub.platforms.overview', [$p->slug, 'tab' => $tab]))->assertOk();
            }
        }
        $this->get(route('admin.hub.platforms.overview', 'anawlqdrat'))->assertSee('باقة أنا')->assertSee('115.00');
        $this->get(route('admin.hub.platforms.overview', ['anawlqdrat', 'tab' => 'subscribers']))->assertSee('طالب داخلي')->assertSee('طالب خارجي')->assertSee('أب داخلي');
        $this->get(route('admin.hub.platforms.overview', ['anawlqdrat', 'tab' => 'subscribers', 'q' => 'خارجي']))->assertSee('طالب خارجي')->assertDontSee('طالب داخلي');
        $this->get(route('admin.hub.platforms.overview', ['anawlqdrat', 'tab' => 'subscribers', 'account_status' => 'none']))->assertSee('طالب خارجي')->assertDontSee('طالب داخلي');
        $this->get(route('admin.hub.platforms.overview', ['aafaq', 'tab' => 'students', 'linked' => '0', 'q' => '12345']))->assertSee('حساب حر');
        $this->get(route('admin.hub.platforms.overview', ['aafaq', 'tab' => 'students', 'linked' => '1']))->assertDontSee('حساب حر');
        $this->get(route('admin.hub.platforms.overview', ['anawlqdrat', 'tab' => 'bogus']))->assertNotFound();
        $this->get(route('admin.hub.platforms.overview', 'anawlqdrat'))->assertSee(route('admin.hub.platforms.overview', 'aafaq'), false);
    }

    public function test_school_admin_sees_only_own_school_and_not_the_students_tab(): void
    {
        [, , $mine] = $this->fixture();
        $this->as('school-admin', $mine);
        $this->get(route('admin.hub.platforms.overview', 'anawlqdrat'))->assertOk()->assertSee('باقة أنا')->assertDontSee(__('hub_overview.tab_students'));
        $this->get(route('admin.hub.platforms.overview', ['anawlqdrat', 'tab' => 'subscribers']))->assertOk()
            ->assertSee('طالب داخلي')->assertDontSee('طالب خارجي');
        $this->get(route('admin.hub.platforms.overview', ['aafaq', 'tab' => 'students']))->assertForbidden();
    }

    public function test_teacher_and_student_are_forbidden(): void
    {
        $this->fixture();
        foreach (['student', 'parent'] as $role) {
            $this->as($role);
            $this->get(route('admin.hub.platforms.overview', 'anawlqdrat'))->assertForbidden();
        }
        Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'teacher']);
        $this->as('teacher');
        $this->get(route('admin.hub.platforms.overview', 'anawlqdrat'))->assertForbidden();
    }
}
