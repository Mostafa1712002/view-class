<?php

namespace Tests\Feature\RawaniHub;

use App\Models\User;
use App\Modules\RawaniHub\Console\ExpireSubscriptionsCommand;
use App\Modules\RawaniHub\Console\SyncPendingAccountsCommand;
use App\Modules\RawaniHub\Jobs\SyncPlatformAccountJob;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Support\FakePlatform;
use Tests\TestCase;

class SyncJobTest extends TestCase
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

    private FakePlatform $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(PlatformAccountRepository::class, EloquentPlatformAccountRepository::class);
        $this->app->bind(PlatformRepository::class, EloquentPlatformRepository::class);
        $this->app->bind(SubscriptionRepository::class, EloquentSubscriptionRepository::class);
        $this->fake = FakePlatform::install();
    }

    private function platform(array $o = []): HubPlatform
    {
        return HubPlatform::create($o + [
            'slug' => 'alawal', 'name_ar' => 'x', 'base_url' => FakePlatform::BASE, 'secret' => FakePlatform::SECRET,
            'products' => [['code' => 'main']], 'is_enabled' => true,
            'capabilities' => ['parents' => false],
            'required_fields' => ['student' => ['national_id'], 'parent' => ['national_id']],
        ]);
    }

    private function student(array $a = []): User
    {
        return User::factory()->create($a + ['national_id' => (string) random_int(1000000000, 1999999999)]);
    }

    private function account(HubPlatform $p, User $u, array $a = []): HubPlatformAccount
    {
        return HubPlatformAccount::create($a + ['platform_id' => $p->id, 'role' => 'student', 'user_id' => $u->id, 'status' => 'pending', 'needs_sync' => true, 'managed' => true]);
    }

    private function runJob(HubPlatformAccount $acc, int $attempt = 1, ?int $expectRelease = null): void
    {
        $job = new SyncPlatformAccountJob($acc->id);
        $q = Mockery::mock(Job::class);
        $q->shouldReceive('attempts')->andReturn($attempt);
        $expectRelease === null ? $q->shouldNotReceive('release') : $q->shouldReceive('release')->once()->with($expectRelease);
        $job->setJob($q);
        $this->app->call([$job, 'handle']);
    }

    private function giveSub(User $u, HubPlatform $p, string $from = '-1 day', string $to = '+30 days'): HubSubscription
    {
        return HubSubscription::create([
            'user_id' => $u->id, 'package_id' => \App\Modules\RawaniHub\Models\HubPackage::firstOrCreate(['name_ar' => 'p'], ['duration_days' => 30, 'price' => 1])->id, 'items' => [['platform_id' => $p->id, 'product_code' => 'main']],
            'source' => 'grant', 'status' => 'active', 'starts_at' => now()->modify($from), 'ends_at' => now()->modify($to),
        ]);
    }

    public function test_create_then_update_is_idempotent_and_password_only_on_create(): void
    {
        $p = $this->platform();
        $u = $this->student();
        $this->giveSub($u, $p);
        $acc = $this->account($p, $u);

        $this->runJob($acc);
        $acc->refresh();
        $this->assertSame('active', $acc->status);
        $this->assertNotNull($acc->external_id);
        $this->assertNotNull($acc->login);
        $this->assertFalse($acc->needs_sync);
        $this->assertSame(10, strlen($acc->password));
        $this->assertSame($acc->password, $this->fake->accounts["student:{$u->id}"]['password']);
        $this->assertSame('main', $this->fake->accounts["student:{$u->id}"]['entitlements'][0]['product']);
        $this->assertNotNull($p->fresh()->last_ok_at);

        $acc->refresh()->update(['needs_sync' => true]);
        $this->runJob($acc);
        $this->assertArrayNotHasKey('password', $this->fake->accounts["student:{$u->id}"]['state']);
        $this->assertSame(1, count(array_filter($this->fake->accounts)));
        $this->assertSame('active', $acc->fresh()->status);

        $acc->refresh()->update(['needs_sync' => true, 'password_reset_pending' => true]);
        $this->fake->failNext(503, 'PLATFORM_ERROR');
        $this->runJob($acc, 1, 60);
        $this->assertTrue((bool) $acc->fresh()->password_reset_pending); // kept until a push succeeds
        $this->runJob($acc, 2);
        $this->assertSame($acc->password, $this->fake->accounts["student:{$u->id}"]['state']['password']);
        $this->assertFalse((bool) $acc->fresh()->password_reset_pending);
    }

    public function test_second_account_reuses_the_persons_password(): void
    {
        $p1 = $this->platform();
        $p2 = $this->platform(['slug' => 'aafaq']);
        $u = $this->student();
        $a1 = $this->account($p1, $u);
        $this->runJob($a1);
        $a2 = $this->account($p2, $u);
        $this->runJob($a2);
        $this->assertSame($a1->fresh()->password, $a2->fresh()->password);
    }

    public function test_not_dirty_account_is_a_no_op(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student(), ['needs_sync' => false]);
        $this->runJob($acc);
        $this->assertCount(0, $this->fake->requests);
    }

    public function test_disabled_state_syncs_as_disabled(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student(), ['enabled' => false]);
        $this->runJob($acc);
        $this->assertSame('disabled', $acc->fresh()->status);
    }

    public function test_needs_info_makes_no_http_call(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student(['national_id' => null]));
        $this->runJob($acc);
        $acc->refresh();
        $this->assertSame('needs_info', $acc->status);
        $this->assertSame('missing: national_id', $acc->last_error);
        $this->assertCount(0, $this->fake->requests);
    }

    public function test_disabled_platform_is_skipped_and_stays_dirty(): void
    {
        $p = $this->platform(['is_enabled' => false]);
        $acc = $this->account($p, $this->student());
        $this->runJob($acc);
        $acc->refresh();
        $this->assertTrue($acc->needs_sync);
        $this->assertSame('pending', $acc->status);
        $this->assertCount(0, $this->fake->requests);
    }

    public function test_conflict_waits_for_admin_and_never_auto_links(): void
    {
        $p = $this->platform();
        $u = $this->student(['national_id' => '1111111111']);
        $this->fake->conflictOn('student', '1111111111', '9001', 'old-login');
        $acc = $this->account($p, $u);
        $this->runJob($acc);
        $acc->refresh();
        $this->assertSame('conflict', $acc->status);
        $this->assertNull($acc->external_id);
        $this->assertStringContainsString('ACCOUNT_EXISTS', $acc->last_error);
        $this->assertFalse($acc->needs_sync);
        $this->assertCount(1, $this->fake->requests);
        $log = \App\Models\ActivityLog::where('action', 'hub.account.conflict')->sole();
        $this->assertSame(['pending', 'conflict'], [$log->old_values['status'], $log->new_values['status']]);
    }

    public function test_a_reset_landing_during_the_call_is_not_cleared(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        $this->runJob($acc);
        $sent = $acc->fresh()->password;
        $acc->refresh()->update(['needs_sync' => true, 'password_reset_pending' => true]);
        $this->fake->during = fn () => HubPlatformAccount::find($acc->id)->update(['password' => 'newer-reset', 'password_reset_pending' => true, 'needs_sync' => true]);
        $this->runJob($acc);
        $acc->refresh();
        $this->assertNotSame('newer-reset', $sent);
        $this->assertSame('newer-reset', $acc->password);
        $this->assertTrue($acc->password_reset_pending); // still owed to the platform
        $this->assertTrue($acc->needs_sync);
        $this->assertSame('active', $acc->status);
    }

    public function test_enabled_platform_without_a_secret_is_skipped(): void
    {
        $p = $this->platform(['secret' => null]);
        $acc = $this->account($p, $this->student());
        $this->runJob($acc);
        $this->assertCount(0, $this->fake->requests);
        $this->assertTrue($acc->fresh()->needs_sync);
    }

    public function test_invalid_is_stored_as_failed_error(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        $this->fake->failNext(422, 'VALIDATION_FAILED', ['grade' => 'bad']);
        $this->runJob($acc);
        $acc->refresh();
        $this->assertSame('failed', $acc->status);
        $this->assertStringContainsString('VALIDATION_FAILED', $acc->last_error);
        $this->assertFalse($acc->needs_sync);
    }

    public function test_auth_fails_at_once_without_retry_and_marks_the_platform(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        $this->fake->failNext(401, 'INVALID_SIGNATURE');
        $this->runJob($acc); // no release: no backoff
        $this->assertStringContainsString('auth failing', $p->fresh()->last_error);
        $acc->refresh();
        $this->assertSame('failed', $acc->status);
        $this->assertStringContainsString('INVALID_SIGNATURE', $acc->last_error);
        $this->assertFalse($acc->needs_sync);
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'hub.account.failed')->count());
    }

    public function test_identifier_taken_is_a_conflict_for_admin_review(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        $this->fake->failNext(409, 'IDENTIFIER_TAKEN', ['external_id' => '77']);
        $this->runJob($acc);
        $acc->refresh();
        $this->assertSame('conflict', $acc->status);
        $this->assertSame('77', $acc->conflictExternalId());
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'hub.account.conflict')->count());
    }

    public function test_retryable_backs_off_exponentially_then_fails_after_the_last_try(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        foreach ([1 => 60, 2 => 300, 3 => 900, 4 => 3600, 5 => 21600] as $attempt => $delay) {
            $this->fake->failNext(503, 'PLATFORM_ERROR');
            $this->runJob($acc, $attempt, expectRelease: $delay);
            $acc->refresh();
            $this->assertSame('pending', $acc->status);
            $this->assertTrue($acc->needs_sync);
            $this->assertSame($attempt, (int) $acc->attempts);
        }
        $this->fake->failNext(429, 'PLATFORM_ERROR');
        $this->runJob($acc, 6);
        $acc->refresh();
        $this->assertSame('failed', $acc->status);
        $this->assertSame(6, (int) $acc->attempts);
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'hub.account.failed')->count());
    }

    public function test_recovery_after_a_retry_succeeds_and_resets_attempts(): void
    {
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        $this->fake->failNext(500, 'PLATFORM_ERROR');
        $this->runJob($acc, 1, expectRelease: 60);
        $this->runJob($acc, 2);
        $acc->refresh();
        $this->assertSame('active', $acc->status);
        $this->assertSame(0, (int) $acc->attempts);
        $this->assertNull($acc->last_error);
    }

    public function test_student_waits_for_parent_then_parent_activation_wakes_it(): void
    {
        $p = $this->platform(['capabilities' => ['parents' => true]]);
        $child = $this->student();
        $parent = $this->student();
        DB::table('parent_student')->insert(['parent_id' => $parent->id, 'student_id' => $child->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()]);
        $childAcc = $this->account($p, $child);
        $parentAcc = $this->account($p, $parent, ['role' => 'parent']);

        $this->runJob($childAcc);
        $this->assertCount(0, $this->fake->requests);
        $this->assertSame('pending', $childAcc->fresh()->status);
        $this->assertTrue($childAcc->fresh()->needs_sync);

        Bus::fake();
        $this->runJob($parentAcc);
        $this->assertSame('active', $parentAcc->fresh()->status);
        Bus::assertDispatched(SyncPlatformAccountJob::class, fn ($j) => $j->accountId === $childAcc->id);
        $this->assertArrayNotHasKey('entitlements_x', $this->fake->accounts["parent:{$parent->id}"]);
        $this->assertSame([], $this->fake->accounts["parent:{$parent->id}"]['entitlements']);
    }

    public function test_secrets_are_never_logged(): void
    {
        Log::spy();
        $p = $this->platform();
        $acc = $this->account($p, $this->student());
        $this->fake->failNext(503, 'PLATFORM_ERROR');
        $this->runJob($acc, 1, expectRelease: 60);
        $this->assertStringNotContainsString($acc->fresh()->password ?? 'none', (string) $acc->fresh()->last_error);
        $this->assertStringNotContainsString(FakePlatform::SECRET, (string) $acc->fresh()->last_error);
        Log::shouldNotHaveReceived('error');
    }

    public function test_expire_command_expires_due_and_syncs_the_entitlement_away(): void
    {
        $p = $this->platform();
        $u = $this->student();
        $this->giveSub($u, $p, '-40 days', '-1 day');
        $live = $this->giveSub($u, $p, '-5 days', '+10 days');
        $acc = $this->account($p, $u, ['needs_sync' => false, 'status' => 'active', 'external_id' => '5']);
        $this->fake->accounts["student:{$u->id}"] = ['external_id' => '5', 'login' => 'l', 'managed' => true];

        Bus::fake();
        $this->app[Kernel::class]->registerCommand($this->app->make(ExpireSubscriptionsCommand::class));
        $this->artisan('hub:expire')->assertSuccessful();

        $this->assertSame(1, HubSubscription::where('status', 'expired')->count());
        $this->assertSame('active', $live->fresh()->status);
        $this->assertTrue($acc->fresh()->needs_sync);
        Bus::assertDispatched(SyncPlatformAccountJob::class, fn ($j) => $j->accountId === $acc->id);

        Bus::fake([]); // let the job run for real
        $this->runJob($acc->fresh());
        $this->assertCount(1, $this->fake->accounts["student:{$u->id}"]['entitlements']); // only the live window left

        $live->update(['status' => 'expired', 'ends_at' => now()->subHour()]);
        $acc->refresh()->update(['needs_sync' => true]);
        $this->runJob($acc->fresh());
        $this->assertSame([], $this->fake->accounts["student:{$u->id}"]['entitlements']);
    }

    public function test_sync_pending_dispatches_only_stale_dirty_accounts(): void
    {
        $p = $this->platform();
        $stale = $this->account($p, $this->student());
        $fresh = $this->account($p, $this->student());
        $clean = $this->account($p, $this->student(), ['needs_sync' => false]);
        $failed = $this->account($p, $this->student(), ['status' => 'failed']);
        foreach ([$stale, $clean, $failed] as $a) {
            DB::table('hub_platform_accounts')->where('id', $a->id)->update(['updated_at' => now()->subMinutes(30)]);
        }

        Bus::fake();
        $this->app[Kernel::class]->registerCommand($this->app->make(SyncPendingAccountsCommand::class));
        $this->artisan('hub:sync-pending')->assertSuccessful();
        Bus::assertDispatchedTimes(SyncPlatformAccountJob::class, 1);
        Bus::assertDispatched(SyncPlatformAccountJob::class, fn ($j) => $j->accountId === $stale->id);
    }

    public function test_dispatcher_queues_the_job_after_commit(): void
    {
        Bus::fake();
        DB::transaction(function () {
            app(\App\Modules\RawaniHub\Services\AccountSyncDispatcher::class)->afterCommit([7, 7, 8]);
            Bus::assertNothingDispatched();
        });
        Bus::assertDispatchedTimes(SyncPlatformAccountJob::class, 2);
    }
}
