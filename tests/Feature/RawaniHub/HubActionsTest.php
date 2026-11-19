<?php

namespace Tests\Feature\RawaniHub;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Actions\CancelSubscription;
use App\Modules\RawaniHub\Actions\CreatePackage;
use App\Modules\RawaniHub\Actions\FindOrCreateBeneficiary;
use App\Modules\RawaniHub\Actions\GrantPackage;
use App\Modules\RawaniHub\Actions\LinkPlatformAccount;
use App\Modules\RawaniHub\Actions\NationalIdCollision;
use App\Modules\RawaniHub\Actions\ResetAccountPassword;
use App\Modules\RawaniHub\Actions\SetAccountEnabled;
use App\Modules\RawaniHub\Actions\UpdatePackage;
use App\Modules\RawaniHub\DTOs\BeneficiaryData;
use App\Modules\RawaniHub\DTOs\GrantData;
use App\Modules\RawaniHub\DTOs\PackageData;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Repositories\EloquentPackageRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HubActionsTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_20_100000_create_hub_tables'] as $pattern) {
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
        foreach (['super-admin', 'student', 'parent'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        }
        config(['rawani_hub.default_school_id' => null]);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::where('slug', 'super-admin')->first());
        $this->actingAs($u);

        return $u;
    }

    private function platform(string $slug, bool $parents = false): HubPlatform
    {
        return HubPlatform::create(['slug' => $slug, 'name_ar' => $slug, 'base_url' => 'https://x.test',
            'products' => [['code' => 'reading'], ['code' => 'verbal']], 'capabilities' => ['parents' => $parents], 'is_enabled' => true]);
    }

    private function packageData(array $items, array $o = []): PackageData
    {
        return new PackageData($o['name'] ?? 'باقة', $o['price'] ?? '115.00', $o['days'] ?? 30, $items,
            isPurchasable: $o['purchasable'] ?? false);
    }

    private function makePackage(User $admin, array $items, int $days = 30)
    {
        return app(CreatePackage::class)->execute($admin, $this->packageData($items, ['days' => $days]));
    }

    public function test_non_super_admin_is_rejected_everywhere(): void
    {
        $p = $this->platform('alawal');
        $nobody = User::factory()->create();
        $this->expectException(AuthorizationException::class);
        app(CreatePackage::class)->execute($nobody, $this->packageData([['platform_id' => $p->id, 'product_code' => 'reading']]));
    }

    public function test_package_create_update_validation_and_audit(): void
    {
        $admin = $this->admin();
        $p = $this->platform('alawal');
        $pkg = app(CreatePackage::class)->execute($admin, $this->packageData([['platform_id' => $p->id, 'product_code' => 'reading']], ['price' => 115]));
        $this->assertSame('115.00', $pkg->price);
        $this->assertSame($admin->id, $pkg->created_by);
        $this->assertCount(1, $pkg->items);
        $this->assertSame(1, ActivityLog::where('action', 'hub.package.create')->count());

        $upd = app(UpdatePackage::class)->execute($admin, $pkg, $this->packageData(
            [['platform_id' => $p->id, 'product_code' => 'verbal']], ['price' => '200', 'name' => 'جديد']));
        $this->assertSame('verbal', $upd->items->first()->product_code);
        $this->assertSame('جديد', $upd->name_ar);
        $this->assertSame(1, ActivityLog::where('action', 'hub.package.update')->count());

        foreach ([
            [[], []],
            [[['platform_id' => $p->id, 'product_code' => 'nope']], []],
            [[['platform_id' => 999, 'product_code' => 'reading']], []],
            [[['platform_id' => $p->id, 'product_code' => 'reading']], ['price' => -1]],
            [[['platform_id' => $p->id, 'product_code' => 'reading']], ['price' => 0, 'purchasable' => true]],
            [[['platform_id' => $p->id, 'product_code' => 'reading']], ['days' => 0]],
            [[['platform_id' => $p->id, 'product_code' => 'reading'], ['platform_id' => $p->id, 'product_code' => 'reading']], []],
        ] as [$items, $o]) {
            try {
                app(CreatePackage::class)->execute($admin, $this->packageData($items, $o));
                $this->fail('expected validation failure');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(1, DB::table('hub_packages')->count());
    }

    public function test_grant_is_free_multi_beneficiary_creates_accounts_and_stacks(): void
    {
        $admin = $this->admin();
        $al = $this->platform('alawal', parents: true);
        $aq = $this->platform('aafaq');
        $pkg = $this->makePackage($admin, [
            ['platform_id' => $al->id, 'product_code' => 'reading'], ['platform_id' => $aq->id, 'product_code' => 'reading'],
        ]);
        $s1 = User::factory()->create();
        $s2 = User::factory()->create();
        $parent = User::factory()->create();
        DB::table('parent_student')->insert(['parent_id' => $parent->id, 'student_id' => $s1->id, 'is_primary' => 1]);

        $subs = app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [$s1->id, $s2->id, $s1->id], note: 'هدية'));

        $this->assertCount(2, $subs);
        $first = $subs->first();
        $this->assertSame('grant', $first->source);
        $this->assertSame('active', $first->status);
        $this->assertSame('0.00', $first->price);
        $this->assertSame($admin->id, $first->granted_by);
        $this->assertNull($first->buyer_id);
        $this->assertSame(30, (int) $first->starts_at->diffInDays($first->ends_at));
        $this->assertCount(2, $first->items);
        // s1: student on 2 platforms + parent on alawal only; s2: student on 2 platforms
        $this->assertSame(3, HubPlatformAccount::where('user_id', $s1->id)->orWhere('user_id', $parent->id)->count());
        $this->assertSame(1, HubPlatformAccount::where(['user_id' => $parent->id, 'role' => 'parent', 'platform_id' => $al->id])->count());
        $this->assertSame(0, HubPlatformAccount::where(['role' => 'parent', 'platform_id' => $aq->id])->count());
        $this->assertSame(0, HubPlatformAccount::where('needs_sync', false)->count());
        $this->assertSame(2, ActivityLog::where('action', 'hub.subscription.grant')->count());

        $again = app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [$s1->id]))->first();
        $this->assertTrue($again->starts_at->equalTo($first->ends_at), 'renewal starts at the previous end');
        $this->assertSame(2, HubPlatformAccount::where('user_id', $s1->id)->count());
    }

    public function test_grant_rejects_inactive_package_and_unknown_user(): void
    {
        $admin = $this->admin();
        $p = $this->platform('alawal');
        $pkg = $this->makePackage($admin, [['platform_id' => $p->id, 'product_code' => 'reading']]);
        try {
            app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [99999]));
            $this->fail();
        } catch (ValidationException) {
        }
        $pkg->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [$admin->id]));
    }

    public function test_cancel_marks_cancelled_dirties_accounts_and_only_once(): void
    {
        $admin = $this->admin();
        $p = $this->platform('alawal');
        $pkg = $this->makePackage($admin, [['platform_id' => $p->id, 'product_code' => 'reading']]);
        $s = User::factory()->create();
        $sub = app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [$s->id]))->first();
        HubPlatformAccount::query()->update(['needs_sync' => false]);

        $c = app(CancelSubscription::class)->execute($admin, $sub->id, 'خطأ');
        $this->assertSame('cancelled', $c->status);
        $this->assertStringContainsString('خطأ', $c->note);
        $this->assertSame(1, HubPlatformAccount::where('needs_sync', true)->count());
        $this->assertSame(1, ActivityLog::where('action', 'hub.subscription.cancel')->count());

        $this->expectException(ValidationException::class);
        app(CancelSubscription::class)->execute($admin, $sub->id);
    }

    public function test_beneficiary_creation_parent_link_and_collision_rules(): void
    {
        $admin = $this->admin();
        $school = DB::table('schools')->insertGetId(['name' => 's', 'code' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        config(['rawani_hub.default_school_id' => $school]);
        $dto = new BeneficiaryData('طالب', 'male', '1000000001', 'primary', 3, parentName: 'ولي', parentNationalId: '2000000002');

        $s = app(FindOrCreateBeneficiary::class)->execute($dto);
        $this->assertSame($school, (int) $s->school_id);
        $this->assertStringEndsWith('@viewclass.local', $s->email);
        $this->assertTrue($s->hasRole('student'));
        $this->assertSame(3, (int) HubProfile::find($s->id)->grade);
        $parent = User::where('national_id', '2000000002')->first();
        $this->assertTrue($parent->hasRole('parent'));
        $this->assertSame(1, DB::table('parent_student')->where(['parent_id' => $parent->id, 'student_id' => $s->id, 'is_primary' => 1])->count());

        // admin: same ID returns the existing student, creates nothing
        $this->assertSame($s->id, app(FindOrCreateBeneficiary::class)->execute($dto)->id);
        $this->assertSame(2, User::whereIn('national_id', ['1000000001', '2000000002'])->count());

        // self-service: blocked
        $this->expectException(NationalIdCollision::class);
        app(FindOrCreateBeneficiary::class)->execute($dto, selfService: true);
    }

    public function test_beneficiary_parent_id_of_non_parent_collides_and_validation(): void
    {
        $this->admin();
        config(['rawani_hub.default_school_id' => DB::table('schools')->insertGetId(['name' => 's', 'code' => 'x', 'created_at' => now(), 'updated_at' => now()])]);
        User::factory()->create(['national_id' => '3000000003']);
        try {
            app(FindOrCreateBeneficiary::class)->execute(new BeneficiaryData('ط', 'male', '1000000009', 'primary', 1, parentName: 'و', parentNationalId: '3000000003'));
            $this->fail();
        } catch (NationalIdCollision) {
        }
        $this->assertSame(0, User::where('national_id', '1000000009')->count(), 'rolled back');
        $this->expectException(ValidationException::class);
        app(FindOrCreateBeneficiary::class)->execute(new BeneficiaryData('ط', 'x', '12', 'primary', 1));
    }

    public function test_conflict_is_never_linked_without_admin_action(): void
    {
        $admin = $this->admin();
        $p = $this->platform('alawal');
        $pkg = $this->makePackage($admin, [['platform_id' => $p->id, 'product_code' => 'reading']]);
        $s = User::factory()->create();
        $sub = app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [$s->id]))->first();
        $acc = HubPlatformAccount::where('user_id', $s->id)->first();
        $acc->update(['status' => 'conflict', 'external_id' => '5531', 'login' => 'old@x.test']);

        // grant again / cancel / enable do not link it
        app(GrantPackage::class)->execute($admin, new GrantData($pkg->id, [$s->id]));
        app(CancelSubscription::class)->execute($admin, $sub->id);
        app(SetAccountEnabled::class)->execute($admin, $acc->id, true);
        $acc->refresh();
        $this->assertSame('conflict', $acc->status);
        $this->assertNull($acc->link_confirmed_by);
        $this->assertTrue($acc->managed);

        $linked = app(LinkPlatformAccount::class)->execute($admin, $acc->id);
        $this->assertSame('pending', $linked->status);
        $this->assertFalse($linked->managed);
        $this->assertNull($linked->password);
        $this->assertSame($admin->id, $linked->link_confirmed_by);
        $this->assertSame('5531', $linked->external_id);
        $this->assertTrue($linked->needs_sync);
        $this->assertSame(1, ActivityLog::where('action', 'hub.account.link')->count());
    }

    public function test_link_unlinked_mirror_absorbs_conflict_row_and_rejects_bad_targets(): void
    {
        $admin = $this->admin();
        $p = $this->platform('alawal');
        $s = User::factory()->create();
        $conflict = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'user_id' => $s->id, 'status' => 'conflict', 'password' => 'x']);
        $mirror = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'external_id' => '77', 'status' => 'unlinked']);

        $r = app(LinkPlatformAccount::class)->execute($admin, $mirror->id, userId: $s->id);
        $this->assertSame($mirror->id, $r->id);
        $this->assertSame($s->id, $r->user_id);
        $this->assertNull(HubPlatformAccount::find($conflict->id));

        // an active managed row cannot be "linked"
        $active = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'parent', 'user_id' => $s->id, 'status' => 'active']);
        $this->expectException(ValidationException::class);
        app(LinkPlatformAccount::class)->execute($admin, $active->id);
    }

    public function test_reset_password_only_managed_one_password_per_person_no_audit_leak(): void
    {
        $admin = $this->admin();
        $a = $this->platform('alawal');
        $b = $this->platform('aafaq');
        $s = User::factory()->create();
        $m1 = HubPlatformAccount::create(['platform_id' => $a->id, 'role' => 'student', 'user_id' => $s->id, 'managed' => true, 'password' => 'old']);
        $m2 = HubPlatformAccount::create(['platform_id' => $b->id, 'role' => 'student', 'user_id' => $s->id, 'managed' => true, 'password' => 'old']);

        $pw = app(ResetAccountPassword::class)->execute($admin, $m1->id);
        $this->assertSame(10, strlen($pw));
        $this->assertSame($pw, $m1->fresh()->password);
        $this->assertSame($pw, $m2->fresh()->password);
        $this->assertTrue($m2->fresh()->needs_sync);
        $log = ActivityLog::where('action', 'hub.account.reset_password')->first();
        $this->assertStringNotContainsString($pw, json_encode($log->toArray()));

        $linked = HubPlatformAccount::create(['platform_id' => $a->id, 'role' => 'parent', 'user_id' => $s->id, 'managed' => false]);
        $this->expectException(ValidationException::class);
        app(ResetAccountPassword::class)->execute($admin, $linked->id);
    }

    public function test_set_enabled_toggles_marks_dirty_and_rejects_unlinked(): void
    {
        $admin = $this->admin();
        $p = $this->platform('alawal');
        $s = User::factory()->create();
        $acc = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'user_id' => $s->id, 'status' => 'active']);

        $r = app(SetAccountEnabled::class)->execute($admin, $acc->id, false);
        $this->assertFalse($r->enabled);
        $this->assertTrue($r->needs_sync);
        $this->assertSame(1, ActivityLog::where('action', 'hub.account.disable')->count());
        app(SetAccountEnabled::class)->execute($admin, $acc->id, true);
        $this->assertSame(1, ActivityLog::where('action', 'hub.account.enable')->count());

        $mirror = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'external_id' => '9', 'status' => 'unlinked']);
        $this->expectException(ValidationException::class);
        app(SetAccountEnabled::class)->execute($admin, $mirror->id, false);
    }

    public function test_every_account_action_requires_super_admin(): void
    {
        $p = $this->platform('alawal');
        $s = User::factory()->create();
        $acc = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'user_id' => $s->id, 'managed' => true]);
        foreach ([
            fn () => app(SetAccountEnabled::class)->execute($s, $acc->id, false),
            fn () => app(ResetAccountPassword::class)->execute($s, $acc->id),
            fn () => app(LinkPlatformAccount::class)->execute($s, $acc->id),
            fn () => app(CancelSubscription::class)->execute($s, 1),
            fn () => app(GrantPackage::class)->execute($s, new GrantData(1, [$s->id])),
        ] as $call) {
            try {
                $call();
                $this->fail('expected AuthorizationException');
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }
}
