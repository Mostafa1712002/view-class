<?php

namespace Tests\Unit\RawaniHub;

use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Services\AccountStateBuilder;
use App\Modules\RawaniHub\Services\EntitlementCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;

class EntitlementStateTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = Carbon::parse('2026-11-19 10:00:00', 'UTC');
    }

    private function sub(string $from, string $to, array $items, string $status = 'active'): HubSubscription
    {
        return new HubSubscription([
            'status' => $status,
            'starts_at' => Carbon::parse($from, 'UTC')->setTimezone(config('app.timezone'))->toDateTimeString(),
            'ends_at' => Carbon::parse($to, 'UTC')->setTimezone(config('app.timezone'))->toDateTimeString(),
            'items' => array_map(fn ($i) => ['platform_id' => $i[0], 'product_code' => $i[1]], $items),
        ]);
    }

    private function calc(array $subs, int $platform = 1): array
    {
        return (new EntitlementCalculator)->entitlements($subs, $platform, $this->now);
    }

    public function test_single_subscription_window_is_utc_iso(): void
    {
        $r = $this->calc([$this->sub('2026-11-19 00:00:00', '2027-11-19 00:00:00', [[1, 'main']])]);
        $this->assertSame([['product' => 'main', 'from' => '2026-11-19T00:00:00Z', 'until' => '2027-11-19T00:00:00Z']], $r);
    }

    public function test_renewals_stack_min_from_max_until(): void
    {
        $r = $this->calc([
            $this->sub('2026-12-19 00:00:00', '2027-01-19 00:00:00', [[1, 'main']]),
            $this->sub('2026-11-19 00:00:00', '2026-12-19 00:00:00', [[1, 'main']]),
        ]);
        $this->assertCount(1, $r);
        $this->assertSame('2026-11-19T00:00:00Z', $r[0]['from']);
        $this->assertSame('2027-01-19T00:00:00Z', $r[0]['until']);
    }

    public function test_overlapping_subscriptions_merge(): void
    {
        $r = $this->calc([
            $this->sub('2026-11-01 00:00:00', '2026-12-01 00:00:00', [[1, 'main']]),
            $this->sub('2026-11-15 00:00:00', '2026-11-20 00:00:00', [[1, 'main']]),
        ]);
        $this->assertSame('2026-11-01T00:00:00Z', $r[0]['from']);
        $this->assertSame('2026-12-01T00:00:00Z', $r[0]['until']);
    }

    public function test_gap_is_not_bridged_current_window_wins(): void
    {
        $r = $this->calc([
            $this->sub('2026-11-01 00:00:00', '2026-12-01 00:00:00', [[1, 'main']]),
            $this->sub('2027-02-01 00:00:00', '2027-03-01 00:00:00', [[1, 'main']]),
        ]);
        $this->assertCount(1, $r);
        $this->assertSame('2026-11-01T00:00:00Z', $r[0]['from']);
        $this->assertSame('2026-12-01T00:00:00Z', $r[0]['until']);
    }

    public function test_gap_with_only_future_windows_returns_the_nearest(): void
    {
        $r = $this->calc([
            $this->sub('2027-02-01 00:00:00', '2027-03-01 00:00:00', [[1, 'main']]),
            $this->sub('2026-12-01 00:00:00', '2026-12-15 00:00:00', [[1, 'main']]),
        ]);
        $this->assertSame('2026-12-01T00:00:00Z', $r[0]['from']);
        $this->assertSame('2026-12-15T00:00:00Z', $r[0]['until']);
    }

    public function test_inactive_statuses_do_not_count(): void
    {
        foreach (['pending', 'expired', 'cancelled', 'refunded'] as $status) {
            $this->assertSame([], $this->calc([$this->sub('2026-11-01 00:00:00', '2027-01-01 00:00:00', [[1, 'main']], $status)]), $status);
        }
    }

    public function test_refunded_does_not_shrink_the_other_active_window(): void
    {
        $r = $this->calc([
            $this->sub('2026-11-01 00:00:00', '2027-06-01 00:00:00', [[1, 'main']], 'refunded'),
            $this->sub('2026-11-01 00:00:00', '2026-12-01 00:00:00', [[1, 'main']]),
        ]);
        $this->assertSame('2026-12-01T00:00:00Z', $r[0]['until']);
    }

    public function test_end_date_edges(): void
    {
        $this->assertSame([], $this->calc([$this->sub('2026-10-19 00:00:00', '2026-11-19 10:00:00', [[1, 'main']])]), 'ends exactly now = expired');
        $this->assertCount(1, $this->calc([$this->sub('2026-10-19 00:00:00', '2026-11-19 10:00:01', [[1, 'main']])]), 'one second left');
        $this->assertSame([], $this->calc([$this->sub('2026-10-01 00:00:00', '2026-11-01 00:00:00', [[1, 'main']])]), 'active status but past end (before hub:expire runs)');
    }

    public function test_future_start_counts(): void
    {
        $r = $this->calc([$this->sub('2026-12-01 00:00:00', '2027-01-01 00:00:00', [[1, 'main']])]);
        $this->assertSame('2026-12-01T00:00:00Z', $r[0]['from']);
    }

    public function test_two_products_and_other_platform_filtered(): void
    {
        $subs = [
            $this->sub('2026-11-01 00:00:00', '2027-01-01 00:00:00', [[2, 'verbal'], [2, 'reading'], [1, 'main']]),
            $this->sub('2026-11-10 00:00:00', '2027-03-01 00:00:00', [[2, 'reading']]),
        ];
        $r = $this->calc($subs, 2);
        $this->assertSame(['reading', 'verbal'], array_column($r, 'product'));
        $this->assertSame('2027-03-01T00:00:00Z', $r[0]['until']);
        $this->assertSame('2027-01-01T00:00:00Z', $r[1]['until']);
        $this->assertSame(['main'], array_column($this->calc($subs, 1), 'product'));
        $this->assertSame([], $this->calc($subs, 3));
    }

    public function test_missing_dates_are_skipped(): void
    {
        $s = new HubSubscription(['status' => 'active', 'items' => [['platform_id' => 1, 'product_code' => 'main']]]);
        $this->assertSame([], $this->calc([$s]));
    }

    // ---- AccountStateBuilder ----

    private function platform(string $slug, array $required, array $caps = []): HubPlatform
    {
        $p = new HubPlatform(['slug' => $slug, 'required_fields' => $required, 'capabilities' => $caps]);
        $p->id = 1;

        return $p;
    }

    private function user(array $a = []): User
    {
        $u = new User(array_merge(['name' => 'Sara', 'gender' => 'female', 'national_id' => '1234567890', 'email' => 'sara@x.com', 'phone' => '+966500000000'], $a));
        $u->id = 42;

        return $u;
    }

    private function account(string $role = 'student', array $a = []): HubPlatformAccount
    {
        return new HubPlatformAccount(array_merge(['role' => $role, 'enabled' => true, 'managed' => true], $a));
    }

    private function build(HubPlatform $p, HubPlatformAccount $acc, User $u, ?HubProfile $prof = null, array $subs = [], ?User $parent = null, bool $pw = false)
    {
        return (new AccountStateBuilder(new EntitlementCalculator))->build($acc, $p, $u, $prof, $subs, $this->now, $parent, $pw);
    }

    public function test_full_student_payload_for_alawal_with_parent(): void
    {
        $p = $this->platform('alawal', ['student' => ['national_id', 'gender', 'stage', 'grade']], ['parents' => true]);
        $parent = $this->user(['name' => 'Dad']);
        $parent->id = 77;
        $s = $this->build($p, $this->account(), $this->user(), new HubProfile(['stage' => 'primary', 'grade' => '4']),
            [$this->sub('2026-11-19 00:00:00', '2027-11-19 00:00:00', [[1, 'main']])], $parent);

        $this->assertTrue($s->ready());
        $payload = $s->toPayload();
        $this->assertSame(77, $payload['profile']['parent_rawani_id']);
        $this->assertSame(4, $payload['profile']['grade']);
        $this->assertSame('primary', $payload['profile']['stage']);
        $this->assertTrue($payload['enabled']);
        $this->assertSame('main', $payload['entitlements'][0]['product']);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('link_external_id', $payload);
    }

    public function test_missing_required_fields_are_listed(): void
    {
        $p = $this->platform('alawal', ['student' => ['national_id', 'stage', 'grade']]);
        $s = $this->build($p, $this->account(), $this->user(['national_id' => '']), null);
        $this->assertFalse($s->ready());
        $this->assertSame(['national_id', 'stage', 'grade'], $s->missing);
    }

    public function test_parent_row_has_no_entitlements_no_student_fields_and_stays_enabled(): void
    {
        $p = $this->platform('alawal', ['student' => ['grade'], 'parent' => ['national_id', 'name']], ['parents' => true]);
        $s = $this->build($p, $this->account('parent'), $this->user(), new HubProfile(['stage' => 'primary', 'grade' => 3]),
            [$this->sub('2026-11-19 00:00:00', '2027-11-19 00:00:00', [[1, 'main']])]);
        $this->assertTrue($s->ready());
        $this->assertSame([], $s->entitlements);
        $this->assertTrue($s->enabled);
        $this->assertArrayNotHasKey('grade', $s->profile);
        $this->assertArrayNotHasKey('parent_rawani_id', $s->profile);
        $this->assertSame('1234567890', $s->profile['national_id']);

        $this->assertSame(['national_id'], $this->build($p, $this->account('parent'), $this->user(['national_id' => null]))->missing);
    }

    public function test_parent_id_only_on_platforms_with_parents(): void
    {
        $parent = $this->user();
        $parent->id = 77;
        $s = $this->build($this->platform('anawlqdrat', []), $this->account(), $this->user(), null, [], $parent);
        $this->assertArrayNotHasKey('parent_rawani_id', $s->profile);
    }

    public function test_aafaq_uses_generated_placeholder_email_and_both_products(): void
    {
        $p = $this->platform('aafaq', ['student' => ['name', 'gender', 'grade']]);
        $s = $this->build($p, $this->account(), $this->user(), new HubProfile(['grade' => 5]),
            [$this->sub('2026-11-19 00:00:00', '2027-11-19 00:00:00', [[1, 'verbal'], [1, 'reading']])]);
        $this->assertSame('rw42@rawaniedu.com', $s->profile['email']);
        $this->assertSame(['reading', 'verbal'], array_column($s->entitlements, 'product'));
    }

    public function test_disabled_account_and_password_only_when_requested(): void
    {
        $p = $this->platform('anawlqdrat', []);
        $acc = $this->account('student', ['enabled' => false, 'password' => 'pw-1']);
        $this->assertFalse($this->build($p, $acc, $this->user())->enabled);
        $this->assertNull($this->build($p, $acc, $this->user())->password);
        $this->assertSame('pw-1', $this->build($p, $acc, $this->user(), null, [], null, true)->toPayload()['password']);
    }

    public function test_link_external_id_only_for_confirmed_unmanaged_link(): void
    {
        $p = $this->platform('alawal', []);
        $linked = $this->account('student', ['managed' => false, 'link_confirmed_by' => 9, 'external_id' => 5531]);
        $this->assertSame('5531', $this->build($p, $linked, $this->user())->toPayload()['link_external_id']);
        $unconfirmed = $this->account('student', ['managed' => false, 'external_id' => 5531]);
        $this->assertNull($this->build($p, $unconfirmed, $this->user())->linkExternalId);
        $managed = $this->account('student', ['managed' => true, 'link_confirmed_by' => 9, 'external_id' => 5531]);
        $this->assertNull($this->build($p, $managed, $this->user())->linkExternalId);
    }
}
