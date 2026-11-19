<?php

namespace Tests\Feature\RawaniHub;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Console\MirrorPlatformPeopleCommand;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Repositories\EloquentPackageRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\EloquentPlatformRepository;
use App\Modules\RawaniHub\Repositories\EloquentSubscriptionRepository;
use App\Modules\RawaniHub\Services\LinkCandidates;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Support\FakePlatform;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    private FakePlatform $fake;

    private HubPlatform $platform;

    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*',
            '2026_11_20_100000_create_hub_tables', '2026_11_20_100100_*', '2026_11_20_100400_add_directory_mirror_columns'] as $pattern) {
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
        config(['rawani_hub.default_school_id' => DB::table('schools')->insertGetId(['name' => 's', 'code' => 'x', 'created_at' => now(), 'updated_at' => now()])]);
        Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/web.php'));
        app('router')->getRoutes()->refreshNameLookups();
        $this->app[Kernel::class]->registerCommand($this->app->make(MirrorPlatformPeopleCommand::class));

        $this->fake = FakePlatform::install();
        $this->platform = HubPlatform::create(['slug' => 'alawal', 'name_ar' => 'الأول', 'base_url' => FakePlatform::BASE, 'secret' => FakePlatform::SECRET,
            'products' => [['code' => 'main', 'name_ar' => 'الأول']], 'capabilities' => ['parents' => false], 'required_fields' => [], 'is_enabled' => true]);
    }

    private function item(int $id, array $extra = []): array
    {
        return $extra + ['external_id' => (string) $id, 'role' => 'student', 'rawani_id' => null, 'name' => "شخص $id", 'email' => null, 'phone' => null,
            'national_id' => null, 'parent_external_id' => null, 'org' => null, 'active' => true, 'access' => [], 'created_at' => '2026-01-01T00:00:00Z'];
    }

    private function people(int ...$ids): void
    {
        $this->fake->people = array_map(fn ($id) => $this->item($id), $ids);
    }

    private function mirror(string ...$flags): int
    {
        $args = ['--platform' => 'alawal', '--sync' => true, '--per' => 2];
        foreach ($flags as $f) {
            $args[$f] = true;
        }

        return Artisan::call('hub:mirror', $args);
    }

    private function peopleCalls(): array
    {
        return array_values(array_filter(array_map(fn ($r) => $r->url(), $this->fake->requests), fn ($u) => str_contains($u, '/people')));
    }

    private function as(string $role): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::where('slug', $role)->first());
        $this->actingAs($u);

        return $u;
    }

    private function person(string $role, array $attrs = []): User
    {
        $u = User::factory()->create($attrs);
        $u->roles()->attach(Role::where('slug', $role)->first());

        return $u;
    }

    private function row(string $ext): HubPlatformAccount
    {
        return HubPlatformAccount::where(['platform_id' => $this->platform->id, 'external_id' => $ext])->firstOrFail();
    }

    public function test_mirror_walks_every_page_into_unlinked_rows_and_keeps_only_whitelisted_fields(): void
    {
        $this->people(1, 2, 3, 4);
        $this->fake->people[] = $this->item(5, ['email' => ' A@X.Test ', 'phone' => '+966 50 123-4567', 'national_id' => '1000000005',
            'access' => [['product' => 'main', 'until' => null, 'source' => 'other', 'token' => 'leak-me']], 'password' => 'leak-me', 'api_token' => 'leak-me']);

        $this->assertSame(0, $this->mirror());

        $this->assertCount(3, $this->peopleCalls()); // 2 + 2 + 1
        $rows = HubPlatformAccount::orderBy('external_id')->get();
        $this->assertCount(5, $rows);
        $this->assertTrue($rows->every(fn ($r) => $r->status === 'unlinked' && $r->user_id === null && ! $r->managed && $r->seen_at));
        $five = $this->row('5');
        $this->assertSame('a@x.test', $five->email);
        $this->assertSame('966501234567', $five->phone);
        $this->assertSame([['product' => 'main', 'until' => null, 'source' => 'other']], $five->access);
        $this->assertStringNotContainsString('leak-me', json_encode(DB::table('hub_platform_accounts')->get()));
        $p = $this->platform->refresh();
        $this->assertNull($p->mirror_started_at);
        $this->assertNull($p->mirror_cursor);
        $this->assertNotNull($p->mirror_finished_at);
    }

    public function test_updates_touch_only_mirror_columns_and_rawani_id_never_links(): void
    {
        $linked = $this->person('student');
        $timedOut = $this->person('student');
        $stranger = $this->person('student');
        $acc = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'user_id' => $linked->id, 'external_id' => '7',
            'login' => 'l7', 'password' => 'pw', 'managed' => true, 'status' => 'active', 'name' => 'old']);
        HubPlatformAccount::whereKey($acc->id)->toBase()->update(['updated_at' => '2026-01-01 00:00:00']);
        $waiting = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'user_id' => $timedOut->id, 'managed' => true, 'status' => 'pending']);
        $this->fake->people = [
            $this->item(7, ['rawani_id' => $linked->id, 'name' => 'new', 'active' => false]),
            $this->item(8, ['rawani_id' => $timedOut->id]),
            $this->item(9, ['rawani_id' => $stranger->id]),
        ];

        $this->mirror();

        $acc->refresh();
        $this->assertSame(['new', false, 'active', 'pw', 'l7', $linked->id, true], [$acc->name, $acc->platform_active, $acc->status, $acc->password, $acc->login, $acc->user_id, $acc->managed]);
        $this->assertSame('2026-01-01 00:00:00', $acc->getRawOriginal('updated_at'), 'mirror must not reset the sync job clock');
        $this->assertSame('8', $waiting->refresh()->external_id, 'a timed-out create gets its external_id');
        $this->assertSame('pending', $waiting->status);
        $this->assertNull($this->row('9')->user_id, 'a rawani_id alone never links');
        $this->assertSame(3, HubPlatformAccount::count());
    }

    public function test_a_failed_page_parks_the_walk_and_the_next_run_resumes_at_its_cursor(): void
    {
        $this->people(1, 2, 3, 4, 5);
        $calls = 0;
        $this->fake->during = function () use (&$calls) {
            if (++$calls === 2) {
                $this->fake->failNext(503, 'PLATFORM_ERROR');
            }
        };

        $this->assertSame(1, $this->mirror());
        $this->assertSame(2, HubPlatformAccount::count());
        $p = $this->platform->refresh();
        $this->assertSame('2', $p->mirror_cursor);
        $this->assertNotNull($p->mirror_started_at);

        $this->assertSame(0, $this->mirror());
        $this->assertSame(5, HubPlatformAccount::count());
        $this->assertStringContainsString('cursor=2', $this->peopleCalls()[2]);
        $this->assertNull($this->platform->refresh()->mirror_started_at);

        // a stale copy of the job (old cursor) does nothing
        $this->fake->requests = [];
        dispatch_sync(new \App\Modules\RawaniHub\Jobs\MirrorPlatformPeopleJob($this->platform->id, '2026-01-01 00:00:00', '2'));
        $this->assertSame([], $this->fake->requests);
    }

    public function test_full_walk_marks_unseen_rows_missing_and_seen_again_restores_them(): void
    {
        $user = $this->person('student');
        $this->people(1, 2, 3);
        $this->mirror();
        HubPlatformAccount::where('external_id', '3')->update(['user_id' => $user->id, 'link_confirmed_by' => $user->id, 'status' => 'active']);
        $this->travel(5)->minutes();

        $this->people(1);
        $this->mirror();
        $this->assertSame(0, HubPlatformAccount::where('status', 'missing')->count(), 'only the weekly --full walk sweeps');

        // a --full walk that breaks midway never sweeps
        $this->people(1, 4, 5);
        $this->fake->failures = [];
        $calls = 0;
        $this->fake->during = function () use (&$calls) {
            if (++$calls === 2) {
                $this->fake->failNext(503, 'PLATFORM_ERROR');
            }
        };
        $this->mirror('--full');
        $this->assertSame(0, HubPlatformAccount::where('status', 'missing')->count());
        $this->fake->during = null;
        $this->mirror(); // resumes the same --full walk to its end
        $this->assertSame(['2', '3'], HubPlatformAccount::where('status', 'missing')->orderBy('external_id')->pluck('external_id')->all());
        $this->assertSame(1, ActivityLog::where('action', 'hub.mirror.sweep')->count());

        $this->travel(5)->minutes();
        $this->people(1, 2, 3, 4, 5);
        $this->mirror();
        $this->assertSame('unlinked', $this->row('2')->status);
        $back = $this->row('3');
        $this->assertSame('active', $back->status, 'a linked row back from missing is re-synced (sync queue in tests)');
        $this->assertNotNull(collect($this->fake->requests)->first(fn ($r) => $r->method() === 'PUT'));
        $this->assertSame($user->id, $back->user_id);

        // an empty walk never marks everything missing
        $this->travel(5)->minutes();
        $this->fake->people = [];
        $this->mirror('--full');
        $this->assertSame(0, HubPlatformAccount::where('status', 'missing')->count());
    }

    public function test_candidates_rank_national_id_then_real_email_then_phone_and_never_name(): void
    {
        $byId = $this->person('student', ['national_id' => '1000000009', 'name' => 'x1']);
        $byEmail = $this->person('student', ['email' => 'kid@x.test', 'name' => 'x2']);
        $byPhone = $this->person('student', ['phone' => '0501234567', 'name' => 'x3']);
        $this->person('student', ['name' => 'نفس الاسم']);
        $this->person('parent', ['phone' => '0501234567']); // wrong role
        $taken = $this->person('student', ['national_id' => '1000000009']);
        HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'user_id' => $taken->id, 'external_id' => '77', 'status' => 'active']);
        $this->fake->people = [$this->item(5, ['name' => 'نفس الاسم', 'national_id' => '1000000009', 'email' => 'KID@x.test', 'phone' => '050 123 4567'])];
        $this->mirror();

        $c = app(LinkCandidates::class)->peopleFor($this->row('5'));
        $this->assertSame([[$byId->id, 'national_id'], [$byEmail->id, 'email'], [$byPhone->id, 'phone']],
            $c->map(fn ($x) => [$x['user']->id, $x['reason']])->all());

        $this->person('student', ['email' => 'rw42@rawaniedu.com']);
        $placeholder = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'external_id' => '6', 'status' => 'unlinked', 'email' => 'rw42@rawaniedu.com']);
        $this->assertCount(0, app(LinkCandidates::class)->peopleFor($placeholder));
    }

    public function test_phone_matching_uses_one_saudi_form_on_both_sides(): void
    {
        $this->as('super-admin');
        $local = $this->person('student', ['phone' => '0501234567']);
        $intl = $this->person('student', ['phone' => '00966501234567']);
        $this->person('student', ['phone' => '0551234567']); // a different number
        $this->fake->people = [$this->item(5, ['phone' => '+966 50 123 4567'])];
        $this->mirror();
        $row = $this->row('5');
        $this->assertSame('966501234567', $row->phone);

        $found = app(LinkCandidates::class)->peopleFor($row)->map(fn ($c) => [$c['user']->id, $c['reason']])->all();
        $this->assertEqualsCanonicalizing([[$local->id, 'phone'], [$intl->id, 'phone']], $found);
        $noPhone = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'external_id' => '6', 'status' => 'unlinked']);
        $this->assertEqualsCanonicalizing([$local->id, $intl->id],
            app(LinkCandidates::class)->peopleFor($noPhone, '+966501234567')->map(fn ($c) => $c['user']->id)->all()); // admin lookup

        // a person's conflict row finds the mirrored account typed another way
        $conflict = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'user_id' => $local->id, 'status' => 'conflict']);
        $this->assertSame([$row->id], app(LinkCandidates::class)->accountsFor($conflict)->map(fn ($c) => $c['account']->id)->all());

        foreach (['0501234567', '501234567', '+966501234567', '00966501234567'] as $q) {
            $this->get('/admin/hub/directory?'.http_build_query(['q' => $q]))->assertOk()->assertSee('شخص 5');
        }
        $this->get('/admin/hub/directory?q=0551234567')->assertOk()->assertDontSee('شخص 5');
    }

    public function test_admin_link_sets_confirmer_and_the_sync_pushes_link_external_id(): void
    {
        $student = $this->person('student');
        $this->fake->people = [$this->item(5531)];
        $this->mirror();
        $row = $this->row('5531');

        $this->as('school-admin');
        $this->post("/admin/hub/accounts/{$row->id}/link", ['user_id' => $student->id])->assertForbidden();
        $this->get('/admin/hub/directory')->assertForbidden();

        $admin = $this->as('super-admin');
        $this->post("/admin/hub/accounts/{$row->id}/link", ['user_id' => $student->id])->assertRedirect("/admin/hub/people/{$student->id}");

        $row->refresh();
        $this->assertSame([$student->id, $admin->id, false], [$row->user_id, $row->link_confirmed_by, $row->managed]);
        $put = collect($this->fake->requests)->first(fn ($r) => $r->method() === 'PUT');
        $this->assertNotNull($put, 'the link is pushed by the sync job');
        $this->assertSame('5531', $put->data()['link_external_id']);
        $this->assertArrayNotHasKey('password', $put->data());
        $this->assertSame('active', $row->status);
        $this->assertSame(1, ActivityLog::where('action', 'hub.account.link')->count());
    }

    public function test_create_person_from_a_row_and_refuse_a_taken_national_id(): void
    {
        $this->person('student', ['national_id' => '1000000002']);
        $this->fake->people = [$this->item(1, ['national_id' => '1000000001', 'email' => 'real@x.test']), $this->item(2, ['national_id' => '1000000002'])];
        $this->mirror();
        $this->as('super-admin');

        $this->post("/admin/hub/accounts/{$this->row('1')->id}/create-person", ['name' => 'طالب', 'gender' => 'male', 'national_id' => '1000000001', 'email' => 'real@x.test', 'grade' => 4, 'stage' => 'primary'])
            ->assertRedirect();
        $person = User::where('national_id', '1000000001')->firstOrFail();
        $this->assertTrue($person->hasRole('student'));
        $this->assertSame('real@x.test', $person->email);
        $this->assertSame((int) config('rawani_hub.default_school_id'), (int) $person->school_id);
        $this->assertSame($person->id, $this->row('1')->user_id);
        $this->assertNotNull($this->row('1')->link_confirmed_by);
        $this->assertSame(1, ActivityLog::where('action', 'hub.person.create_from_account')->count());

        $before = User::count();
        $this->post("/admin/hub/accounts/{$this->row('2')->id}/create-person", ['name' => 'x', 'gender' => 'male', 'national_id' => '1000000002'])
            ->assertSessionHasErrors('national_id');
        $this->assertSame($before, User::count());
        $this->assertNull($this->row('2')->user_id);
    }

    public function test_unlink_is_not_shipped(): void
    {
        // Deferred: rawani/v1 has no detach call, and a later re-provision would hit the (platform, role, user_id) key.
        $admin = $this->as('super-admin');
        $u = $this->person('student');
        $linked = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'user_id' => $u->id, 'external_id' => '1',
            'managed' => false, 'link_confirmed_by' => $admin->id, 'status' => 'active']);

        $this->assertFalse(Route::has('admin.hub.accounts.unlink'));
        $this->delete("/admin/hub/accounts/{$linked->id}/link")->assertStatus(405);
        $this->assertSame($u->id, $linked->refresh()->user_id);
        $this->get("/admin/hub/accounts/{$linked->id}/link")->assertOk()->assertDontSee('_method');
    }

    public function test_directory_tabs_filters_and_platform_relatives_on_both_pages(): void
    {
        $parent = $this->person('parent', ['name' => 'وليّ رواني']);
        $this->fake->people = [
            $this->item(88, ['role' => 'parent', 'name' => 'وليّ المنصة', 'national_id' => '2000000088']),
            $this->item(5, ['name' => 'ابن المنصة', 'parent_external_id' => '88', 'active' => false]),
        ];
        $this->mirror();
        HubPlatformAccount::where(['external_id' => '88', 'role' => 'parent'])->update(['user_id' => $parent->id, 'status' => 'active']);
        $this->as('super-admin');

        $this->get('/admin/hub/directory?status=unlinked')->assertOk()->assertSee('ابن المنصة')->assertDontSee('وليّ المنصة')->assertSee(__('hub_directory.platform_inactive'));
        $this->get('/admin/hub/directory?q=2000000088')->assertOk()->assertSee('وليّ المنصة')->assertDontSee('ابن المنصة');
        $this->get('/admin/hub/directory?linked=1')->assertOk()->assertSee('وليّ رواني');
        $this->get('/admin/hub/directory?tab=people&q=وليّ')->assertOk()->assertSee('وليّ رواني');
        $this->get("/admin/hub/people/{$parent->id}")->assertOk()->assertSee(__('hub_directory.platform_children'))->assertSee('ابن المنصة');
        $this->get("/admin/hub/accounts/{$this->row('5')->id}/link")->assertOk()->assertSee(__('hub_directory.platform_parent'))->assertSee('وليّ المنصة')
            ->assertSee(__('hub_directory.create_person'));
    }

    public function test_the_queued_walk_chains_page_jobs_to_the_end(): void
    {
        $this->people(1, 2, 3, 4, 5);

        $this->assertSame(0, Artisan::call('hub:mirror', ['--platform' => 'alawal', '--per' => 2]));

        $this->assertSame(5, HubPlatformAccount::count());
        $this->assertCount(3, $this->peopleCalls());
        $this->assertNull($this->platform->refresh()->mirror_started_at);
    }

    public function test_conflict_row_lists_matching_mirror_rows_and_links_the_chosen_one(): void
    {
        $admin = $this->as('super-admin');
        $u = $this->person('student', ['national_id' => '1000000042']);
        $conflict = HubPlatformAccount::create(['platform_id' => $this->platform->id, 'role' => 'student', 'user_id' => $u->id, 'status' => 'conflict', 'managed' => true]);
        $this->fake->people = [$this->item(42, ['name' => 'حساب قديم', 'national_id' => '1000000042']), $this->item(43, ['name' => 'زيد غير مطابق'])];
        $this->mirror();
        $mirror = $this->row('42');

        $this->get("/admin/hub/accounts/{$conflict->id}/link")->assertOk()->assertSee('حساب قديم')->assertDontSee('زيد غير مطابق')->assertSee(__('hub_directory.reason_national_id'));

        $this->post("/admin/hub/accounts/{$conflict->id}/link", ['external_id' => '42'])->assertRedirect("/admin/hub/people/{$u->id}");
        $this->assertNull(HubPlatformAccount::find($conflict->id));
        $mirror->refresh();
        $this->assertSame([$u->id, $admin->id, false], [$mirror->user_id, $mirror->link_confirmed_by, $mirror->managed]);
    }

    public function test_teacher_rows_are_never_linked(): void
    {
        $this->as('super-admin');
        $this->fake->people = [$this->item(9, ['role' => 'teacher'])];
        $this->mirror();
        $row = HubPlatformAccount::where(['external_id' => '9', 'role' => 'teacher'])->firstOrFail();

        $this->post("/admin/hub/accounts/{$row->id}/link", ['user_id' => $this->person('student')->id])->assertSessionHasErrors('account');
        $this->assertNull($row->refresh()->user_id);
    }
}
