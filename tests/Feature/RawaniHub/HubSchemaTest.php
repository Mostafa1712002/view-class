<?php

namespace Tests\Feature\RawaniHub;

use App\Models\User;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\EloquentPackageRepository;
use App\Modules\RawaniHub\Repositories\EloquentPaymentRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HubSchemaTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_20_*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function platform(string $slug = 'alawal'): HubPlatform
    {
        return (new EloquentPlatformRepository)->create([
            'slug' => $slug, 'name_ar' => $slug, 'base_url' => 'https://x.test/api/rawani/v1',
            'secret' => 's3cret', 'products' => [['code' => 'reading']], 'is_enabled' => true,
        ]);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_secret_and_password_are_encrypted_at_rest_and_hidden(): void
    {
        $platform = $this->platform();
        $user = $this->user();
        $account = (new EloquentPlatformAccountRepository)->firstOrCreateForUser($platform->id, 'student', $user->id);
        $account->update(['password' => 'pw-123']);

        $this->assertNotSame('s3cret', DB::table('hub_platforms')->value('secret'));
        $this->assertNotSame('pw-123', DB::table('hub_platform_accounts')->value('password'));
        $this->assertSame('s3cret', $platform->fresh()->secret);
        $this->assertSame('pw-123', $account->fresh()->password);
        $this->assertArrayNotHasKey('secret', $platform->fresh()->toArray());
        $this->assertArrayNotHasKey('password', $account->fresh()->toArray());
        $this->assertSame([['code' => 'reading']], $platform->fresh()->products);
    }

    public function test_account_uniques_hold_and_two_null_users_coexist(): void
    {
        $platform = $this->platform();
        $repo = new EloquentPlatformAccountRepository;
        $make = fn (array $a) => HubPlatformAccount::create($a + ['platform_id' => $platform->id, 'role' => 'student']);

        $make(['user_id' => null, 'external_id' => 'a1']);
        $make(['user_id' => null, 'external_id' => 'a2']); // two NULL user_id rows on one platform
        $this->assertSame(2, HubPlatformAccount::count());

        $u = $this->user();
        $make(['user_id' => $u->id]);
        $this->assertSame(1, HubPlatformAccount::where('user_id', $u->id)->count());
        $this->assertSame(HubPlatformAccount::where('user_id', $u->id)->first()->id,
            $repo->firstOrCreateForUser($platform->id, 'student', $u->id)->id); // idempotent

        $this->expectException(QueryException::class);
        $make(['user_id' => null, 'external_id' => 'a1']); // duplicate (platform, role, external_id)
    }

    public function test_one_user_one_account_per_platform_and_role(): void
    {
        $platform = $this->platform();
        $u = $this->user();
        HubPlatformAccount::create(['platform_id' => $platform->id, 'role' => 'student', 'user_id' => $u->id]);
        // same user, other role is fine
        HubPlatformAccount::create(['platform_id' => $platform->id, 'role' => 'parent', 'user_id' => $u->id]);
        $this->expectException(QueryException::class);
        HubPlatformAccount::create(['platform_id' => $platform->id, 'role' => 'student', 'user_id' => $u->id]);
    }

    public function test_platform_slug_package_item_and_payment_uniques(): void
    {
        $platform = $this->platform();
        $package = (new EloquentPackageRepository)->create(['name_ar' => 'باقة', 'duration_days' => 30, 'price' => 10]);
        $repo = new EloquentPackageRepository;
        $repo->syncItems($package, [['platform_id' => $platform->id, 'product_code' => 'reading']]);
        $repo->syncItems($package, [['platform_id' => $platform->id, 'product_code' => 'reading']]); // replace, no dup
        $this->assertSame(1, $package->items()->count());

        try {
            $this->platform('alawal');
            $this->fail('duplicate slug accepted');
        } catch (QueryException) {
        }

        $u = $this->user();
        $sub = (new EloquentSubscriptionRepository)->create([
            'user_id' => $u->id, 'package_id' => $package->id, 'items' => [['platform_id' => $platform->id, 'product_code' => 'reading']],
            'source' => 'grant', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30),
        ]);
        $pay = new EloquentPaymentRepository;
        $pay->create(['subscription_id' => $sub->id, 'buyer_id' => $u->id, 'amount' => 1000, 'moyasar_invoice_id' => 'inv1']);
        $this->assertNotNull($pay->findByInvoiceId('inv1'));
        $this->expectException(QueryException::class);
        $pay->create(['subscription_id' => $sub->id, 'buyer_id' => $u->id, 'amount' => 1000]); // 1:1 payment
    }

    public function test_relations_and_subscription_repository(): void
    {
        $platform = $this->platform();
        $package = (new EloquentPackageRepository)->create(['name_ar' => 'باقة', 'duration_days' => 30]);
        $child = $this->user();
        $parent = $this->user();
        $items = [['platform_id' => $platform->id, 'product_code' => 'reading']];
        $repo = new EloquentSubscriptionRepository;
        $sub = $repo->create(['user_id' => $child->id, 'buyer_id' => $parent->id, 'package_id' => $package->id, 'items' => $items,
            'source' => 'purchase', 'status' => 'active', 'starts_at' => now()->subDays(40), 'ends_at' => now()->subDay()]);
        $repo->create(['user_id' => $child->id, 'package_id' => $package->id, 'items' => $items,
            'source' => 'grant', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);

        $this->assertTrue($sub->user->is($child));
        $this->assertTrue($sub->buyer->is($parent));
        $this->assertSame($package->id, $sub->package->id);
        $this->assertSame([['platform_id' => $platform->id, 'product_code' => 'reading']], $sub->items);

        $this->assertEquals(now()->addDays(30)->toDateString(),
            $repo->latestEndFor($child->id, $platform->id, 'reading')->toDateString());
        $this->assertNull($repo->latestEndFor($child->id, $platform->id, 'other'));

        $expired = $repo->expireDue(now());
        $this->assertSame([$sub->id], $expired->pluck('id')->all());
        $this->assertSame(1, $repo->activeForUser($child->id)->count());

        $account = HubPlatformAccount::create(['platform_id' => $platform->id, 'role' => 'student', 'user_id' => $child->id]);
        $this->assertTrue($account->platform->is($platform));
        $this->assertTrue($account->user->is($child));
        $this->assertInstanceOf(HubPayment::class, (new HubPayment)->newInstance());
        $this->assertInstanceOf(HubSubscription::class, $sub);
    }
}
