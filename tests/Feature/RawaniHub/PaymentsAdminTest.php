<?php

namespace Tests\Feature\RawaniHub;

use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\EloquentPaymentRepository;
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

class PaymentsAdminTest extends TestCase
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
        $this->app->bind(PaymentRepository::class, EloquentPaymentRepository::class);
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

    private function payment(string $status = 'paid'): HubPayment
    {
        $p = HubPlatform::create(['slug' => 'alawal', 'name_ar' => 'الأول', 'base_url' => 'https://x.test', 'products' => [['code' => 'reading', 'name_ar' => 'ق']], 'capabilities' => [], 'is_enabled' => true]);
        $pkg = HubPackage::create(['name_ar' => 'باقة', 'price' => '115.00', 'currency' => 'SAR', 'duration_days' => 30, 'is_active' => true]);
        $pkg->items()->create(['platform_id' => $p->id, 'product_code' => 'reading']);
        $buyer = User::factory()->create();
        $sub = HubSubscription::create(['user_id' => $buyer->id, 'buyer_id' => $buyer->id, 'package_id' => $pkg->id, 'items' => [['platform_id' => $p->id, 'product_code' => 'reading']],
            'price' => '115.00', 'source' => 'purchase', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);

        return HubPayment::create(['subscription_id' => $sub->id, 'buyer_id' => $buyer->id, 'amount' => 11500, 'currency' => 'SAR', 'status' => $status,
            'moyasar_invoice_id' => 'inv_1', 'moyasar_payment_id' => 'pay_1', 'paid_at' => now()]);
    }

    public function test_only_super_admin_sees_payments(): void
    {
        $p = $this->payment();
        $this->as('super-admin');
        $this->get('/admin/hub/payments')->assertOk()->assertSee('115.00')->assertSee('inv_1');
        $this->get("/admin/hub/payments/{$p->id}/receipt")->assertOk()->assertSee('pay_1');
        foreach (['school-admin', 'parent', 'student'] as $role) {
            $this->as($role);
            $this->get('/admin/hub/payments')->assertForbidden();
            $this->get("/admin/hub/payments/{$p->id}/receipt")->assertForbidden();
            $this->post("/admin/hub/payments/{$p->id}/refund", ['note' => 'x'])->assertForbidden();
        }
        $this->assertSame('paid', $p->refresh()->status);
    }

    public function test_refund_marks_payment_and_subscription_and_audits(): void
    {
        $p = $this->payment();
        $admin = $this->as('super-admin');
        $this->post("/admin/hub/payments/{$p->id}/refund", [])->assertSessionHasErrors('note');
        $this->post("/admin/hub/payments/{$p->id}/refund", ['note' => 'استرداد من لوحة Moyasar'])->assertSessionHas('success');
        $p->refresh();
        $this->assertSame('refunded', $p->status);
        $this->assertSame($admin->id, $p->refunded_by);
        $this->assertNotNull($p->refunded_at);
        $this->assertSame('refunded', $p->subscription->status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'hub.payment.refund']);
        $this->get('/admin/hub/payments?status=refunded')->assertOk()->assertSee('استرداد من لوحة Moyasar');
        $this->post("/admin/hub/payments/{$p->id}/refund", ['note' => 'again'])->assertSessionHasErrors('payment');
    }

    public function test_unpaid_payment_cannot_be_refunded_or_receipted(): void
    {
        $p = $this->payment('pending');
        $this->as('super-admin');
        $this->post("/admin/hub/payments/{$p->id}/refund", ['note' => 'x'])->assertSessionHasErrors('payment');
        $this->get("/admin/hub/payments/{$p->id}/receipt")->assertNotFound();
    }
}
