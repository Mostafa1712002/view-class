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

class AdminScreensTest extends TestCase
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

    private function as(string $role): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::where('slug', $role)->first());
        $this->actingAs($u);

        return $u;
    }

    private function platform(): HubPlatform
    {
        return HubPlatform::create(['slug' => 'alawal', 'name_ar' => 'الأول', 'base_url' => FakePlatform::BASE,
            'products' => [['code' => 'reading', 'name_ar' => 'القراءة']], 'capabilities' => [], 'is_enabled' => true]);
    }

    private function package(HubPlatform $p): HubPackage
    {
        $pkg = HubPackage::create(['name_ar' => 'باقة', 'price' => '115.00', 'currency' => 'SAR', 'duration_days' => 30, 'is_active' => true]);
        $pkg->items()->create(['platform_id' => $p->id, 'product_code' => 'reading']);

        return $pkg;
    }

    public function test_only_super_admin_reaches_the_pages(): void
    {
        $p = $this->platform();
        $user = $this->as('super-admin');
        $urls = ['platforms', 'platforms/create', "platforms/{$p->id}/edit", 'packages', 'packages/create', 'subscriptions', 'subscriptions/grant', "people/{$user->id}"];
        foreach ($urls as $u) {
            $this->get("/admin/hub/$u")->assertOk();
        }
        foreach (['school-admin', 'parent', 'student'] as $role) {
            $this->as($role);
            foreach ($urls as $u) {
                $this->get("/admin/hub/$u")->assertForbidden();
            }
            $this->post("/admin/hub/platforms/{$p->id}/secret")->assertForbidden();
        }
    }

    public function test_platform_form_secret_is_write_only_and_test_connection(): void
    {
        $this->as('super-admin');
        $this->post('/admin/hub/platforms', [
            'slug' => 'aafaq', 'name_ar' => 'آفاق', 'base_url' => FakePlatform::BASE, 'is_enabled' => 1,
            'products' => '[{"code":"reading","name_ar":"قراءة"}]', 'capabilities' => '{"sso":false}', 'required_fields' => '{}',
            'secret' => 'sneaky',
        ])->assertRedirect();
        $p = HubPlatform::first();
        $this->assertNull($p->secret, 'form must not set the secret');
        $this->assertSame('reading', $p->products[0]['code']);

        $this->post("/admin/hub/platforms/{$p->id}/test")->assertSessionHas('error');

        $res = $this->followingRedirects()->post("/admin/hub/platforms/{$p->id}/secret");
        $secret = $p->refresh()->secret;
        $this->assertSame(64, strlen($secret));
        $res->assertSee($secret);
        $this->get("/admin/hub/platforms/{$p->id}/edit")->assertDontSee($secret)->assertSee(__('hub_admin.secret_configured'));

        FakePlatform::install();
        $p->update(['secret' => FakePlatform::SECRET]);
        $this->post("/admin/hub/platforms/{$p->id}/test")->assertSessionHas('success');
        $this->assertNotNull($p->refresh()->last_ok_at);

        $this->put("/admin/hub/platforms/{$p->id}", ['slug' => 'aafaq', 'name_ar' => 'x', 'base_url' => 'bad', 'products' => 'nope'])
            ->assertSessionHasErrors(['base_url', 'products']);
    }

    public function test_package_create_update_toggle_delete(): void
    {
        $this->as('super-admin');
        $p = $this->platform();
        $this->post('/admin/hub/packages', ['name_ar' => 'جديدة', 'price' => '115', 'duration_days' => 30, 'items' => ["{$p->id}|reading"], 'is_active' => 1, 'sort' => 1])
            ->assertRedirect('/admin/hub/packages');
        $pkg = HubPackage::first();
        $this->assertSame('115.00', $pkg->price);
        $this->assertCount(1, $pkg->items);

        $this->post('/admin/hub/packages', ['name_ar' => 'x', 'price' => '1', 'duration_days' => 30, 'items' => ["{$p->id}|nope"]])->assertSessionHasErrors('items.0');
        $this->post('/admin/hub/packages', ['name_ar' => 'x', 'price' => '1', 'duration_days' => 30])->assertSessionHasErrors('items');

        $this->put("/admin/hub/packages/{$pkg->id}", ['name_ar' => 'معدلة', 'price' => '200', 'duration_days' => 10, 'items' => ["{$p->id}|reading"]])->assertRedirect();
        $this->assertSame('معدلة', $pkg->refresh()->name_ar);
        $this->get("/admin/hub/packages/{$pkg->id}/edit")->assertOk()->assertSee('معدلة');

        $this->patch("/admin/hub/packages/{$pkg->id}/toggle");
        $this->assertTrue($pkg->refresh()->is_active); // update without the checkbox made it inactive, toggle flips it back
        $this->delete("/admin/hub/packages/{$pkg->id}")->assertRedirect();
        $this->assertSoftDeleted('hub_packages', ['id' => $pkg->id]);
    }

    public function test_grant_to_existing_and_new_student_then_cancel(): void
    {
        $admin = $this->as('super-admin');
        $pkg = $this->package($this->platform());
        $kid = User::factory()->create(['national_id' => '1000000001']);

        $this->post('/admin/hub/subscriptions/grant', ['package_id' => $pkg->id, 'mode' => 'existing', 'national_ids' => "1000000001\n", 'note' => 'هدية'])
            ->assertRedirect('/admin/hub/subscriptions');
        $sub = HubSubscription::first();
        $this->assertSame([$kid->id, 'grant', 'active', $admin->id], [$sub->user_id, $sub->source, $sub->status, $sub->granted_by]);

        $this->post('/admin/hub/subscriptions/grant', ['package_id' => $pkg->id, 'mode' => 'existing', 'national_ids' => '9999999999'])->assertSessionHasErrors('national_ids');

        $this->post('/admin/hub/subscriptions/grant', ['package_id' => $pkg->id, 'mode' => 'new', 'name' => 'طالب جديد', 'gender' => 'male',
            'national_id' => '2000000002', 'stage' => 'primary', 'grade' => 3, 'parent_name' => 'ولي', 'parent_national_id' => '3000000003'])->assertRedirect();
        $new = User::where('national_id', '2000000002')->firstOrFail();
        $this->assertTrue($new->hasRole('student'));
        $this->assertSame(1, HubSubscription::where('user_id', $new->id)->count());
        $this->post('/admin/hub/subscriptions/grant', ['package_id' => $pkg->id, 'mode' => 'new', 'name' => 'x', 'gender' => 'male', 'national_id' => '2000000002', 'stage' => 'primary', 'grade' => 3])
            ->assertRedirect(); // admin reuses the existing student; the new grant stacks after the first

        // parent lookup lists the children
        $this->get('/admin/hub/subscriptions/grant?parent_national_id=3000000003')->assertSee('طالب جديد');

        $this->get('/admin/hub/subscriptions?status=active&source=grant')->assertOk()->assertSee($kid->name);
        $this->post("/admin/hub/subscriptions/{$sub->id}/cancel")->assertSessionHas('success');
        $this->assertSame('cancelled', $sub->refresh()->status);
    }

    public function test_person_page_actions_reveal_reset_retry_toggle_and_link(): void
    {
        $this->as('super-admin');
        $p = $this->platform();
        $kid = User::factory()->create();
        $mk = fn (array $a) => HubPlatformAccount::create($a + ['platform_id' => $p->id, 'user_id' => $kid->id, 'role' => 'student']);
        $managed = $mk(['external_id' => '55', 'login' => 'rw55', 'password' => 'Secret12345', 'managed' => true, 'status' => 'active']);
        $this->get("/admin/hub/people/{$kid->id}")->assertOk()->assertSee('rw55')->assertDontSee('Secret12345');

        $this->followingRedirects()->post("/admin/hub/accounts/{$managed->id}/reveal")->assertSee('Secret12345');
        $res = $this->followingRedirects()->post("/admin/hub/accounts/{$managed->id}/reset-password");
        $new = $managed->refresh()->password;
        $this->assertNotSame('Secret12345', $new);
        $res->assertSee($new);

        $this->post("/admin/hub/accounts/{$managed->id}/toggle");
        $this->assertFalse($managed->refresh()->enabled);

        $this->post("/admin/hub/accounts/{$managed->id}/retry")->assertSessionHasErrors('account'); // active, not failed
        $managed->update(['status' => 'failed', 'last_error' => 'boom']);
        $this->post("/admin/hub/accounts/{$managed->id}/retry");
        $this->assertSame('pending', $managed->refresh()->status);

        $kid2 = User::factory()->create();
        $conflict = HubPlatformAccount::create(['platform_id' => $p->id, 'user_id' => $kid2->id, 'role' => 'student', 'status' => 'conflict', 'external_id' => '9001', 'managed' => true]);
        $this->get("/admin/hub/people/{$kid2->id}")->assertOk()->assertSee(__('hub_admin.link_after_review'))->assertSee('alert-warning', false);
        $this->post("/admin/hub/accounts/{$conflict->id}/link", [])->assertSessionHasErrors('external_id');
        $this->post("/admin/hub/accounts/{$conflict->id}/link", ['external_id' => '9001'])->assertRedirect();
        $conflict->refresh();
        $this->assertSame(['pending', false], [$conflict->status, $conflict->managed]);
        $this->assertNotNull($conflict->link_confirmed_by);
    }
}
