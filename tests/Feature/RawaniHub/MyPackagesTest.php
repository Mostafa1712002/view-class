<?php

namespace Tests\Feature\RawaniHub;

use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Actions\StartCheckout;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Repositories\EloquentPackageRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MyPackagesTest extends TestCase
{
    public array $checkoutCalls = [];

    public function refreshDatabase(): void
    {
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
        config(['rawani_hub.default_school_id' => DB::table('schools')->insertGetId(['name' => 's', 'code' => 'x', 'created_at' => now(), 'updated_at' => now()])]);
        Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/my.php'));
        app('router')->getRoutes()->refreshNameLookups();

        // Fake of the Task 3.2 checkout action: records the call and returns the hosted invoice URL.
        $test = $this;
        $this->app->instance(StartCheckout::class, new class($test)
        {
            public function __construct(private MyPackagesTest $t) {}

            public function execute(User $buyer, User $beneficiary, HubPackage $package): string
            {
                $this->t->checkoutCalls[] = [$buyer->id, $beneficiary->id, $package->id];

                return 'https://pay.example.test/invoice/1';
            }
        });
    }

    private function user(string $role, array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['school_id' => config('rawani_hub.default_school_id')]);
        $u->roles()->attach(Role::where('slug', $role)->first());

        return $u;
    }

    private function child(User $parent, array $attrs = []): User
    {
        $c = $this->user('student', $attrs);
        DB::table('parent_student')->insert(['parent_id' => $parent->id, 'student_id' => $c->id, 'relationship' => 'parent',
            'is_primary' => 1, 'can_receive_notifications' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return $c;
    }

    private function alawal(): HubPlatform
    {
        return HubPlatform::create(['slug' => 'alawal', 'name_ar' => 'الأول', 'base_url' => 'https://x.test', 'is_enabled' => true,
            'products' => [['code' => 'main', 'name_ar' => 'الأول']], 'capabilities' => ['parents' => true],
            'required_fields' => ['student' => ['national_id', 'gender', 'stage', 'grade'], 'parent' => ['national_id']]]);
    }

    private function package(HubPlatform $p): HubPackage
    {
        $pkg = HubPackage::create(['name_ar' => 'باقة الأول', 'price' => '115.00', 'currency' => 'SAR', 'duration_days' => 30, 'is_active' => true, 'is_purchasable' => true]);
        $pkg->items()->create(['platform_id' => $p->id, 'product_code' => 'main']);

        return $pkg;
    }

    private function registerData(array $over = []): array
    {
        return $over + ['name' => 'أم سارة', 'gender' => 'female', 'national_id' => '1098765432', 'phone' => '0501234567',
            'email' => 'mom@example.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'];
    }

    public function test_register_creates_a_parent_logs_in_and_lands_on_my_packages(): void
    {
        $this->get('/register')->assertOk()->assertSee(__('hub_my.register_btn'));
        $this->post('/register', $this->registerData(['phone' => '+966 50 123 4567']))->assertRedirect(route('my.packages.index'));

        $user = User::where('email', 'mom@example.test')->firstOrFail();
        $this->assertTrue($user->isParent());
        $this->assertSame(config('rawani_hub.default_school_id'), $user->school_id);
        $this->assertSame('0501234567', $user->phone);
        $this->assertAuthenticatedAs($user);
        $this->get(route('my.packages.index'))->assertOk();
    }

    public function test_register_collision_is_generic_for_email_and_national_id_and_validates_formats(): void
    {
        User::factory()->create(['email' => 'mom@example.test', 'national_id' => '1000000001']);
        User::factory()->create(['national_id' => '1000000002']);
        $before = User::count();

        $byEmail = $this->post('/register', $this->registerData(['email' => 'mom@example.test']));
        $byId = $this->post('/register', $this->registerData(['email' => 'other@example.test', 'national_id' => '1000000002']));
        foreach ([$byEmail, $byId] as $r) {
            $r->assertSessionHasErrors(['collision' => __('hub_my.have_account')]);
            $r->assertSessionDoesntHaveErrors(['email', 'national_id']);
        }
        $this->assertSame($before, User::count());
        $this->assertGuest();

        $this->post('/register', $this->registerData(['phone' => '0412345678', 'national_id' => '12']))->assertSessionHasErrors(['phone', 'national_id']);
    }

    public function test_register_is_rate_limited_and_not_for_logged_in_users(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->get('/register')->assertOk(); // viewing the form (and its redirect after a typo) never counts
            $this->post('/register', ['name' => ''])->assertSessionHasErrors();
        }
        $this->post('/register', ['name' => ''])->assertStatus(429);
        $this->get('/register')->assertOk();

        $this->actingAs($this->user('parent'))->get('/register')->assertRedirect();
    }

    public function test_parent_adds_a_child_linked_only_to_them(): void
    {
        $parent = $this->user('parent', ['national_id' => '1098765432']);
        $this->actingAs($parent)->get('/my/packages/children/create')->assertOk();
        $this->post('/my/packages/children', ['name' => 'سارة', 'gender' => 'female', 'national_id' => '2012345678', 'stage' => 'primary', 'grade' => 4])
            ->assertRedirect(route('my.packages.index'));

        $child = $parent->children()->firstOrFail();
        $this->assertTrue($child->isStudent());
        $this->assertSame(config('rawani_hub.default_school_id'), $child->school_id);
        $this->assertSame(4, (int) HubProfile::find($child->id)->grade);

        // same national ID again: blocked, generic message, nothing created
        $this->post('/my/packages/children', ['name' => 'x', 'gender' => 'male', 'national_id' => '2012345678', 'stage' => 'primary', 'grade' => 1])
            ->assertSessionHasErrors(['collision' => __('hub_my.contact_support')]);
        $this->assertSame(1, $parent->children()->count());
        $this->assertSame(0, $this->user('parent')->children()->count());

        $this->actingAs($this->user('student'))->get('/my/packages/children/create')->assertForbidden();
    }

    public function test_adding_children_is_rate_limited(): void
    {
        $this->actingAs($this->user('parent'));
        for ($i = 0; $i < 10; $i++) {
            $this->post('/my/packages/children', ['name' => ''])->assertSessionHasErrors();
        }
        $this->post('/my/packages/children', ['name' => ''])->assertStatus(429);
    }

    public function test_buy_precheck_shows_the_form_and_blocks_checkout_until_complete(): void
    {
        $pkg = $this->package($this->alawal());
        $parent = $this->user('parent'); // no national ID yet
        $child = $this->child($parent, ['national_id' => null, 'gender' => '']);

        $this->actingAs($parent)->get("/my/packages/{$pkg->id}/buy")->assertOk()
            ->assertSee('name="national_id"', false)->assertSee('name="parent_national_id"', false)->assertDontSee(__('hub_my.pay'));

        $this->post("/my/packages/{$pkg->id}/checkout", ['child_id' => $child->id])->assertSessionHasErrors('missing');
        $this->assertSame([], $this->checkoutCalls);

        $this->post("/my/packages/children/{$child->id}/complete", ['package_id' => $pkg->id, 'national_id' => '2012345678', 'gender' => 'male',
            'stage' => 'primary', 'grade' => 3, 'parent_national_id' => '1098765432'])->assertRedirect("/my/packages/{$pkg->id}/buy");
        $this->assertSame('1098765432', $parent->refresh()->national_id);

        $this->get("/my/packages/{$pkg->id}/buy")->assertSee(__('hub_my.pay'));
        $this->post("/my/packages/{$pkg->id}/checkout", ['child_id' => $child->id])->assertRedirect('https://pay.example.test/invoice/1');
        $this->assertSame([[$parent->id, $child->id, $pkg->id]], $this->checkoutCalls);
    }

    public function test_only_the_primary_parent_can_fill_the_parent_national_id(): void
    {
        $pkg = $this->package($this->alawal());
        $primary = $this->user('parent'); // no national ID: the account provisioning uses
        $child = $this->child($primary, ['national_id' => '2012345678', 'gender' => 'male']);
        HubProfile::create(['user_id' => $child->id, 'stage' => 'primary', 'grade' => 3]);
        $second = $this->user('parent');
        DB::table('parent_student')->insert(['parent_id' => $second->id, 'student_id' => $child->id, 'relationship' => 'parent',
            'is_primary' => 0, 'can_receive_notifications' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($second)->get("/my/packages/{$pkg->id}/buy")->assertOk()
            ->assertDontSee('name="parent_national_id"', false)->assertSee(__('hub_my.needs_support'));
        $this->post("/my/packages/children/{$child->id}/complete", ['package_id' => $pkg->id, 'parent_national_id' => '1098765432']);
        $this->assertNull($second->refresh()->national_id);
        $this->assertNull($primary->refresh()->national_id);

        $this->actingAs($primary)->get("/my/packages/{$pkg->id}/buy")->assertSee('name="parent_national_id"', false);
    }

    public function test_complete_does_not_overwrite_or_steal_a_national_id(): void
    {
        $pkg = $this->package($this->alawal());
        $other = User::factory()->create(['national_id' => '2012345678']);
        $parent = $this->user('parent');
        $child = $this->child($parent, ['national_id' => null]);

        $this->actingAs($parent)->post("/my/packages/children/{$child->id}/complete", ['package_id' => $pkg->id, 'national_id' => '2012345678'])
            ->assertSessionHasErrors(['collision' => __('hub_my.contact_support')]);
        $this->assertNull($child->refresh()->national_id);
        $this->assertSame($other->id, User::where('national_id', '2012345678')->value('id'));
    }

    public function test_parent_cannot_buy_or_complete_for_someone_elses_child(): void
    {
        $pkg = $this->package($this->alawal());
        $mine = $this->user('parent');
        $theirs = $this->child($this->user('parent'));

        $this->actingAs($mine)->post("/my/packages/{$pkg->id}/checkout", ['child_id' => $theirs->id])->assertForbidden();
        $this->post("/my/packages/{$pkg->id}/checkout")->assertForbidden();
        $this->post("/my/packages/children/{$theirs->id}/complete", ['package_id' => $pkg->id, 'gender' => 'male'])->assertForbidden();
        $this->assertSame([], $this->checkoutCalls);
    }

    public function test_unpurchasable_package_is_404(): void
    {
        $pkg = $this->package($this->alawal());
        $pkg->update(['is_purchasable' => false]);
        $this->actingAs($this->user('parent'))->get("/my/packages/{$pkg->id}/buy")->assertNotFound();
    }

    public function test_student_sees_own_packages_and_always_buys_for_themself(): void
    {
        $p = $this->alawal();
        $pkg = $this->package($p);
        $parent = $this->user('parent', ['national_id' => '1098765432']);
        $student = $this->child($parent, ['national_id' => '2012345678', 'gender' => 'male']);
        HubProfile::create(['user_id' => $student->id, 'stage' => 'primary', 'grade' => 3]);
        $stranger = $this->user('student');

        foreach ([$student, $stranger] as $u) {
            HubSubscription::create(['user_id' => $u->id, 'package_id' => $pkg->id, 'items' => [['platform_id' => $p->id, 'product_code' => 'main']],
                'price' => 0, 'source' => 'grant', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30), 'note' => $u->id === $student->id ? 'mine' : 'theirs']);
        }

        $this->actingAs($student)->get('/my/packages')->assertOk()->assertSee('باقة الأول')->assertSee(__('hub_my.sub_status_active'));
        $this->assertCount(1, $this->actingAs($student)->get('/my/packages')->viewData('people')->first()['subscriptions']);
        $this->get("/my/packages/{$pkg->id}/buy")->assertOk()->assertSee(__('hub_my.pay'));

        // a forged child_id is ignored: the beneficiary is the student
        $this->post("/my/packages/{$pkg->id}/checkout", ['child_id' => $stranger->id])->assertRedirect('https://pay.example.test/invoice/1');
        $this->assertSame([[$student->id, $student->id, $pkg->id]], $this->checkoutCalls);
    }

    public function test_student_without_a_parent_is_told_to_contact_support(): void
    {
        $pkg = $this->package($this->alawal());
        $student = $this->user('student', ['national_id' => '2012345678', 'gender' => 'male']);
        HubProfile::create(['user_id' => $student->id, 'stage' => 'primary', 'grade' => 3]);

        $this->actingAs($student)->get("/my/packages/{$pkg->id}/buy")->assertSee(__('hub_my.student_needs_parent'))->assertDontSee(__('hub_my.pay'));
        $this->post("/my/packages/{$pkg->id}/checkout")->assertSessionHasErrors('missing');
        $this->assertSame([], $this->checkoutCalls);
    }

    public function test_credentials_are_shown_only_to_the_owner_and_their_parent(): void
    {
        $p = $this->alawal();
        $pkg = $this->package($p);
        $parent = $this->user('parent');
        $student = $this->child($parent);
        $sub = HubSubscription::create(['user_id' => $student->id, 'buyer_id' => $parent->id, 'package_id' => $pkg->id, 'items' => [['platform_id' => $p->id, 'product_code' => 'main']],
            'price' => 115, 'source' => 'purchase', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);
        $payment = HubPayment::create(['subscription_id' => $sub->id, 'buyer_id' => $parent->id, 'amount' => 11500, 'currency' => 'SAR', 'status' => 'paid', 'paid_at' => now()]);
        HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'user_id' => $student->id, 'external_id' => '9', 'login' => '2012345678',
            'password' => 'Pw-Secret-77', 'managed' => true, 'status' => 'active']);

        foreach ([$student, $parent] as $viewer) {
            $this->actingAs($viewer)->get("/my/packages/return/{$payment->id}")->assertOk()->assertSee('Pw-Secret-77')->assertSee('2012345678')->assertSee(__('hub_my.all_ready'));
            $this->get('/my/packages')->assertSee('Pw-Secret-77');
        }

        $otherParent = $this->user('parent');
        $this->actingAs($otherParent)->get("/my/packages/return/{$payment->id}")->assertForbidden();
        $this->get("/my/packages/return/{$payment->id}/status")->assertForbidden();
        $this->get('/my/packages')->assertDontSee('Pw-Secret-77');
        $this->actingAs($this->user('student'))->get("/my/packages/return/{$payment->id}")->assertForbidden();
    }

    public function test_open_button_only_on_openable_accounts_and_sidebar_link(): void
    {
        Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/my_open.php'));
        app('router')->getRoutes()->refreshNameLookups();
        $p = $this->alawal();
        $parent = $this->user('parent');
        $student = $this->child($parent);
        $open = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'user_id' => $student->id, 'external_id' => '9', 'login' => 'l', 'managed' => true, 'status' => 'active']);
        $unconfirmed = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'parent', 'user_id' => $parent->id, 'external_id' => '8', 'login' => 'l2', 'managed' => false, 'status' => 'active']);

        $html = $this->actingAs($parent)->get('/my/packages')->assertOk()->getContent();
        $this->assertStringContainsString(route('my.packages.accounts.open', $open->id), $html);
        $this->assertStringNotContainsString(route('my.packages.accounts.open', $unconfirmed->id), $html); // linked, not admin-confirmed
        $this->assertStringContainsString('data-label="'.__('shell.nav_my_packages').'"', $html);

        $open->update(['status' => 'pending']);
        $this->get('/my/packages')->assertDontSee(route('my.packages.accounts.open', $open->id));
    }

    public function test_login_page_links_to_register_and_buy(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('hub.register'))->assertSee(__('hub_my.register_title'));
    }

    public function test_return_status_polls_account_statuses_without_credentials(): void
    {
        $p = $this->alawal();
        $pkg = $this->package($p);
        $parent = $this->user('parent');
        $student = $this->child($parent);
        $sub = HubSubscription::create(['user_id' => $student->id, 'buyer_id' => $parent->id, 'package_id' => $pkg->id, 'items' => [['platform_id' => $p->id, 'product_code' => 'main']],
            'price' => 115, 'source' => 'purchase', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);
        $payment = HubPayment::create(['subscription_id' => $sub->id, 'buyer_id' => $parent->id, 'amount' => 11500, 'currency' => 'SAR', 'status' => 'paid', 'paid_at' => now()]);
        $acct = HubPlatformAccount::create(['platform_id' => $p->id, 'role' => 'student', 'user_id' => $student->id, 'password' => 'Pw-Secret-77', 'managed' => true, 'status' => 'pending']);

        $this->actingAs($parent)->get("/my/packages/return/{$payment->id}")->assertSee(__('hub_my.creating_accounts'));
        $res = $this->getJson("/my/packages/return/{$payment->id}/status")->assertOk()->assertJson(['payment' => 'paid', 'done' => false]);
        $this->assertStringNotContainsString('Pw-Secret-77', $res->getContent());

        $acct->update(['status' => 'active']);
        $this->getJson("/my/packages/return/{$payment->id}/status")->assertJson(['done' => true]);
    }
}
