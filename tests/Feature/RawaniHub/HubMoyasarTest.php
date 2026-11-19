<?php

namespace Tests\Feature\RawaniHub;

use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Actions\ApplyPaidPayment;
use App\Modules\RawaniHub\Actions\StartCheckout;
use App\Modules\RawaniHub\Console\ReconcilePaymentsCommand;
use App\Modules\RawaniHub\Jobs\SyncPlatformAccountJob;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Models\HubPaymentEvent;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Repositories\EloquentPackageRepository;
use App\Modules\RawaniHub\Repositories\EloquentPaymentRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HubMoyasarTest extends TestCase
{
    private const API = 'https://api.moyasar.test/v1';

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
        $this->app->bind(PaymentRepository::class, EloquentPaymentRepository::class);
        $this->app->bind(PackageRepository::class, EloquentPackageRepository::class);
        $this->app->bind(PlatformRepository::class, EloquentPlatformRepository::class);
        $this->app->bind(PlatformAccountRepository::class, EloquentPlatformAccountRepository::class);
        $this->app->bind(SubscriptionRepository::class, EloquentSubscriptionRepository::class);
        foreach (['super-admin', 'student', 'parent'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
        }
        config([
            'rawani_hub.default_school_id' => DB::table('schools')->insertGetId(['name' => 's', 'code' => 'x', 'created_at' => now(), 'updated_at' => now()]),
            'moyasar.mode' => 'test', 'moyasar.site' => 'rawani', 'moyasar.api_url' => self::API,
            'moyasar.keys.test.secret' => 'sk_test_fake', 'moyasar.webhook_secret' => 'whsec-1',
        ]);
        Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/web.php'));
        Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/my.php'));
        Route::prefix('api')->middleware('api')->group(base_path('app/Modules/RawaniHub/Routes/api.php'));
        app('router')->getRoutes()->refreshNameLookups();
        $this->app[Kernel::class]->registerCommand($this->app->make(ReconcilePaymentsCommand::class));
        Http::preventStrayRequests();
        Bus::fake([SyncPlatformAccountJob::class]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['school_id' => config('rawani_hub.default_school_id')]);
        $u->roles()->attach(Role::where('slug', $role)->first());

        return $u;
    }

    private function child(User $parent): User
    {
        $c = $this->user('student');
        DB::table('parent_student')->insert(['parent_id' => $parent->id, 'student_id' => $c->id, 'relationship' => 'parent',
            'is_primary' => 1, 'can_receive_notifications' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return $c;
    }

    private function package(): HubPackage
    {
        $p = HubPlatform::firstOrCreate(['slug' => 'anawlqdrat'], ['name_ar' => 'أنا أقدر', 'base_url' => 'https://x.test', 'is_enabled' => true,
            'products' => [['code' => 'reading']], 'capabilities' => [], 'required_fields' => []]);
        $pkg = HubPackage::create(['name_ar' => 'باقة', 'price' => '115.00', 'currency' => 'SAR', 'duration_days' => 30, 'is_active' => true, 'is_purchasable' => true]);
        $pkg->items()->create(['platform_id' => $p->id, 'product_code' => 'reading']);

        return $pkg;
    }

    /** A pending purchase as StartCheckout leaves it, with invoice inv_1. */
    private function pending(array $over = []): HubPayment
    {
        $pkg = $this->package();
        $parent = $this->user('parent');
        $child = $this->child($parent);
        $sub = HubSubscription::create(['user_id' => $child->id, 'buyer_id' => $parent->id, 'package_id' => $pkg->id,
            'items' => [['platform_id' => $pkg->items->first()->platform_id, 'product_code' => 'reading']],
            'price' => '115.00', 'source' => 'purchase', 'status' => 'pending']);

        return HubPayment::create($over + ['subscription_id' => $sub->id, 'buyer_id' => $parent->id, 'amount' => 11500,
            'currency' => 'SAR', 'status' => 'pending', 'moyasar_invoice_id' => 'inv_1']);
    }

    private function remotePayment(array $over = []): array
    {
        return $over + ['id' => 'pay_1', 'status' => 'paid', 'amount' => 11500, 'currency' => 'SAR', 'invoice_id' => 'inv_1',
            'metadata' => ['site' => 'rawani', 'rawani_payment_id' => '1']];
    }

    private function fakeMoyasar(array $invoice, ?array $payment = null): void
    {
        Http::fake([
            self::API.'/invoices/*' => Http::response($invoice + ['id' => 'inv_1', 'amount' => 11500, 'currency' => 'SAR']),
            self::API.'/payments/*' => Http::response($payment ?? $this->remotePayment()),
        ]);
    }

    private function webhook(array $body)
    {
        return $this->postJson('/api/hub/moyasar/webhook', $body + ['id' => 'evt_1', 'secret_token' => 'whsec-1', 'live' => false]);
    }

    public function test_checkout_creates_pending_rows_and_a_site_tagged_invoice_without_email_or_app(): void
    {
        Http::fake([self::API.'/invoices' => Http::response(['id' => 'inv_9', 'status' => 'initiated', 'url' => 'https://checkout.moyasar.com/invoices/inv_9'], 201)]);
        $pkg = $this->package();
        $parent = $this->user('parent');
        $child = $this->child($parent);

        $url = app(StartCheckout::class)->execute($parent, $child, $pkg);

        $this->assertSame('https://checkout.moyasar.com/invoices/inv_9', $url);
        $payment = HubPayment::sole();
        $this->assertSame(['pending', 11500, 'inv_9'], [$payment->status, (int) $payment->amount, $payment->moyasar_invoice_id]);
        $this->assertSame(['pending', 'purchase', $child->id, $parent->id], [$payment->subscription->status, $payment->subscription->source,
            $payment->subscription->user_id, $payment->subscription->buyer_id]);
        Http::assertSent(function (HttpRequest $r) use ($payment) {
            $meta = $r['metadata'];

            return $r->method() === 'POST' && $r->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_fake:'))
                && $r['amount'] === 11500 && $r['currency'] === 'SAR'
                && $meta === ['site' => 'rawani', 'rawani_payment_id' => (string) $payment->id]
                && ! array_key_exists('email', $meta) && ! array_key_exists('app', $meta)
                && $r['success_url'] === route('my.packages.return', $payment->id) && ! isset($r['callback_url']);
        });
    }

    public function test_checkout_for_another_parents_child_is_refused_and_a_gateway_error_leaves_no_open_purchase(): void
    {
        $pkg = $this->package();
        $child = $this->child($this->user('parent'));
        try {
            app(StartCheckout::class)->execute($this->user('parent'), $child, $pkg);
            $this->fail('expected AuthorizationException');
        } catch (AuthorizationException) {
        }
        $this->assertSame(0, HubPayment::count());

        Http::fake([self::API.'/invoices' => Http::response(['message' => 'boom'], 500)]);
        $parent = $this->user('parent');
        try {
            app(StartCheckout::class)->execute($parent, $this->child($parent), $pkg);
            $this->fail('expected the gateway error');
        } catch (\Illuminate\Http\Client\RequestException) {
        }
        $this->assertSame(['failed', 'cancelled'], [HubPayment::sole()->status, HubSubscription::sole()->status]);
    }

    public function test_return_page_applies_a_paid_invoice_once_and_ignores_the_query_string(): void
    {
        $payment = $this->pending();
        $this->fakeMoyasar(['status' => 'paid', 'payments' => [['id' => 'pay_0', 'status' => 'failed'], ['id' => 'pay_1', 'status' => 'paid']]]);

        $this->actingAs($payment->buyer)->get("/my/packages/return/{$payment->id}?status=paid&id=forged")->assertOk();
        $this->actingAs($payment->buyer)->get("/my/packages/return/{$payment->id}")->assertOk();

        $payment->refresh();
        $sub = $payment->subscription;
        $this->assertSame(['paid', 'pay_1', 'active'], [$payment->status, $payment->moyasar_payment_id, $sub->status]);
        $this->assertSame(30, (int) round($sub->starts_at->diffInDays($sub->ends_at)));
        $this->assertSame(1, HubPlatformAccount::where('user_id', $sub->user_id)->where('needs_sync', true)->count());
        Bus::assertDispatchedTimes(SyncPlatformAccountJob::class, 1);
        Http::assertSentCount(2); // second visit: already paid, no gateway call
    }

    public function test_unpaid_invoice_states_activate_nothing(): void
    {
        $payment = $this->pending();
        foreach (['initiated' => [], 'failed' => [['id' => 'pay_0', 'status' => 'failed']], 'expired' => []] as $status => $attempts) {
            $this->fakeMoyasar(['status' => $status, 'payments' => $attempts]);
            $this->actingAs($payment->buyer)->get("/my/packages/return/{$payment->id}")->assertOk();
        }
        $this->assertSame(['pending', 'pending'], [$payment->fresh()->status, $payment->subscription->fresh()->status]);
        Bus::assertNotDispatched(SyncPlatformAccountJob::class);
    }

    public function test_apply_rejects_amount_currency_invoice_or_status_mismatch(): void
    {
        $payment = $this->pending();
        foreach ([['amount' => 100], ['currency' => 'USD'], ['invoice_id' => 'inv_other'], ['status' => 'authorized']] as $bad) {
            Http::fake([self::API.'/payments/*' => Http::response($this->remotePayment($bad))]);
            $this->assertSame(ApplyPaidPayment::REJECTED, app(ApplyPaidPayment::class)->execute($payment, 'pay_1'));
            Http::swap(new \Illuminate\Http\Client\Factory); // reset fakes between cases
            Http::preventStrayRequests();
        }
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_renewal_stacks_after_the_active_window_and_refunded_never_reactivates(): void
    {
        $payment = $this->pending();
        $sub = $payment->subscription;
        $prevEnd = now()->addDays(10)->startOfSecond();
        HubSubscription::create(['user_id' => $sub->user_id, 'package_id' => $sub->package_id, 'items' => $sub->items, 'price' => 0,
            'source' => 'grant', 'status' => 'active', 'starts_at' => now()->subDays(20), 'ends_at' => $prevEnd]);
        $this->fakeMoyasar([], $this->remotePayment());

        $this->assertSame(ApplyPaidPayment::APPLIED, app(ApplyPaidPayment::class)->execute($payment, 'pay_1'));
        $this->assertTrue($sub->fresh()->starts_at->equalTo($prevEnd));

        $payment->refresh()->update(['status' => 'refunded']);
        $this->assertSame(ApplyPaidPayment::DUPLICATE, app(ApplyPaidPayment::class)->execute($payment, 'pay_1'));
        $this->assertSame('refunded', $payment->fresh()->status);
    }

    public function test_webhook_rejects_wrong_or_unconfigured_secret_and_records_nothing(): void
    {
        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment(), 'secret_token' => 'nope'])->assertStatus(401);
        $this->postJson('/api/hub/moyasar/webhook', ['type' => 'payment_paid', 'data' => $this->remotePayment()])->assertStatus(401);
        config(['moyasar.webhook_secret' => '']);
        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment(), 'secret_token' => ''])->assertStatus(401);
        $this->assertSame(0, HubPaymentEvent::count());
    }

    public function test_webhook_ignores_other_sites_records_unmatched_and_applies_ours_once(): void
    {
        $payment = $this->pending();
        $this->fakeMoyasar([], $this->remotePayment());

        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment(['metadata' => ['site' => 'aafaq']])])->assertOk();
        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment(['id' => 'pay_x', 'invoice_id' => 'inv_x', 'metadata' => []])])->assertOk();
        $this->assertSame('pending', $payment->fresh()->status);
        Http::assertNothingSent();

        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment()])->assertOk()->assertJsonPath('data.outcome', 'applied');
        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment()])->assertOk()->assertJsonPath('data.outcome', 'duplicate');

        $this->assertSame(['ignored_site', 'unmatched', 'applied', 'duplicate'], HubPaymentEvent::orderBy('id')->pluck('outcome')->all());
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(1, HubSubscription::where('status', 'active')->count());
        foreach (HubPaymentEvent::all() as $e) {
            $this->assertArrayNotHasKey('secret_token', $e->payload);
        }
        $this->assertStringNotContainsString('whsec-1', DB::table('hub_payment_events')->pluck('payload')->implode(''));
    }

    public function test_webhook_amount_mismatch_is_rejected(): void
    {
        $payment = $this->pending();
        $this->fakeMoyasar([], $this->remotePayment(['amount' => 100]));
        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment(['amount' => 100])])->assertOk()->assertJsonPath('data.outcome', 'rejected');
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_reconcile_applies_paid_invoices_and_gives_up_after_48_hours(): void
    {
        $paid = $this->pending();
        $paid->forceFill(['created_at' => now()->subMinutes(30)])->save();
        $fresh = $this->pending(['moyasar_invoice_id' => 'inv_fresh']);
        $stale = $this->pending(['moyasar_invoice_id' => 'inv_stale']);
        $stale->forceFill(['created_at' => now()->subHours(49)])->save();
        Http::fake([
            self::API.'/invoices/inv_1' => Http::response(['id' => 'inv_1', 'status' => 'paid', 'payments' => [['id' => 'pay_1', 'status' => 'paid']]]),
            self::API.'/invoices/inv_stale' => Http::response(['id' => 'inv_stale', 'status' => 'expired', 'payments' => []]),
            self::API.'/payments/pay_1' => Http::response($this->remotePayment()),
        ]);

        $this->artisan('hub:reconcile-payments')->assertSuccessful();

        $this->assertSame(['paid', 'active'], [$paid->fresh()->status, $paid->subscription->fresh()->status]);
        $this->assertSame(['pending', 'pending'], [$fresh->fresh()->status, $fresh->subscription->fresh()->status]);
        $this->assertSame(['failed', 'cancelled'], [$stale->fresh()->status, $stale->subscription->fresh()->status]);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'inv_fresh'));
    }

    public function test_reconcile_keeps_a_stale_payment_pending_while_the_gateway_is_down(): void
    {
        $stale = $this->pending();
        $stale->forceFill(['created_at' => now()->subHours(49)])->save();
        Http::fake([self::API.'/*' => Http::response('down', 503)]);

        $this->artisan('hub:reconcile-payments')->assertSuccessful();

        $this->assertSame('pending', $stale->fresh()->status);
    }

    public function test_reconcile_never_gives_up_on_captured_money_that_fails_the_checks_and_audits_it_once(): void
    {
        $stale = $this->pending();
        $stale->forceFill(['created_at' => now()->subHours(49)])->save();
        Http::fake([
            self::API.'/invoices/inv_1' => Http::response(['id' => 'inv_1', 'status' => 'paid', 'payments' => [['id' => 'pay_1', 'status' => 'paid']]]),
            self::API.'/payments/pay_1' => Http::response($this->remotePayment(['invoice_id' => null])), // paid, but not provably ours
        ]);

        $this->artisan('hub:reconcile-payments')->assertSuccessful();
        $this->artisan('hub:reconcile-payments')->assertSuccessful();

        $this->assertSame(['pending', 'pending'], [$stale->fresh()->status, $stale->subscription->fresh()->status]);
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'hub.payment.rejected')->where('model_id', $stale->id)->count());
    }

    public function test_admin_check_applies_a_paid_invoice(): void
    {
        $payment = $this->pending();
        $this->fakeMoyasar(['status' => 'paid', 'payments' => [['id' => 'pay_1', 'status' => 'paid']]]);
        $admin = $this->user('super-admin');

        $this->actingAs($payment->buyer)->post("/admin/hub/payments/{$payment->id}/check")->assertForbidden();
        $this->actingAs($admin)->post("/admin/hub/payments/{$payment->id}/check")->assertRedirect();

        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_late_payment_revives_only_a_reconcile_give_up_and_flags_every_other_subscription_state(): void
    {
        // Reconcile gave up → a verified late payment still activates.
        $payment = $this->pending(['status' => 'failed']);
        $payment->subscription->update(['status' => 'cancelled', 'note' => ReconcilePaymentsCommand::GAVE_UP_NOTE]);
        $this->fakeMoyasar([], $this->remotePayment());
        $this->assertSame(ApplyPaidPayment::APPLIED, app(ApplyPaidPayment::class)->execute($payment, 'pay_1'));
        $this->assertSame(['paid', 'active'], [$payment->fresh()->status, $payment->subscription->fresh()->status]);
        Bus::assertDispatchedTimes(SyncPlatformAccountJob::class, 1);

        foreach (['cancelled', 'refunded', 'expired', 'active'] as $i => $status) {
            HubPayment::query()->delete();
            HubSubscription::query()->delete();
            $p = $this->pending(['moyasar_invoice_id' => 'inv_1']);
            $ends = now()->addDays(5)->startOfSecond();
            $p->subscription->update(['status' => $status, 'note' => 'admin', 'starts_at' => now()->subDays(5), 'ends_at' => $ends]);

            $this->assertSame(ApplyPaidPayment::NEEDS_REVIEW, app(ApplyPaidPayment::class)->execute($p, 'pay_1'), $status);
            $sub = $p->subscription->fresh();
            $this->assertSame(['paid', $status], [$p->fresh()->status, $sub->status], $status);
            $this->assertTrue($sub->ends_at->equalTo($ends), $status);
            // An active purchase row with an unpaid payment has no code path; only the audit row marks it.
            $this->assertSame($status === 'active' ? 0 : 1, $this->paymentsFlaggedForReview(), $status);
        }
        $this->assertSame(4, DB::table('activity_logs')->where('action', 'hub.payment.needs_review')->count());
        Bus::assertDispatchedTimes(SyncPlatformAccountJob::class, 1); // only the revival above
    }

    public function test_admin_cancel_fails_the_pending_payment_and_a_later_webhook_lands_in_review(): void
    {
        $payment = $this->pending();
        $admin = $this->user('super-admin');
        app(\App\Modules\RawaniHub\Actions\CancelSubscription::class)->execute($admin, $payment->subscription_id, 'buyer changed mind');
        $this->assertSame(['failed', 'cancelled'], [$payment->fresh()->status, $payment->subscription->fresh()->status]);

        $this->fakeMoyasar([], $this->remotePayment());
        $this->webhook(['type' => 'payment_paid', 'data' => $this->remotePayment()])->assertOk()->assertJsonPath('data.outcome', 'needs_review');

        $this->assertSame(['paid', 'cancelled'], [$payment->fresh()->status, $payment->subscription->fresh()->status]);
        $this->assertSame(1, $this->paymentsFlaggedForReview());
        Bus::assertNotDispatched(SyncPlatformAccountJob::class);
    }

    private function paymentsFlaggedForReview(): int
    {
        return app(PaymentRepository::class)->paginate(['flag' => 'paid_without_active'])->total();
    }
}
