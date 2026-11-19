<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPackageItem;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

/** Per-platform overview for staff: packages, subscribers, mirrored students. School admins see their own school only; the mirror tab is super-admin only. */
class PlatformOverviewController extends Controller
{
    use HasSchoolScope;

    private const SUB_STATUSES = ['pending', 'active', 'expired'];

    public function show(Request $request, HubPlatform $platform)
    {
        $super = $request->user()->isSuperAdmin();
        $schoolId = $this->scopedSchoolId(); // null only for a super-admin without a school scope
        $tab = $request->query('tab', 'packages');
        abort_unless(in_array($tab, ['packages', 'subscribers', 'students'], true), 404);
        abort_if($tab === 'students' && ! $super, 403);

        $packageIds = HubPackageItem::where('platform_id', $platform->id)->pluck('package_id')->unique()->all();
        $data = ['platform' => $platform, 'tab' => $tab, 'isSuper' => $super];

        if ($tab === 'packages') {
            $data['packages'] = $this->packages($platform, $packageIds, $schoolId);
        } elseif ($tab === 'subscribers') {
            $data += $this->subscribers($request, $platform, $packageIds, $schoolId);
        } else {
            $data['students'] = $this->students($request, $platform);
        }

        return view('hub.admin.platform_overview', $data);
    }

    private function subscribed(array $packageIds, ?int $schoolId)
    {
        return HubSubscription::whereIn('package_id', $packageIds)
            ->when($schoolId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('school_id', $schoolId)));
    }

    private function packages(HubPlatform $platform, array $packageIds, ?int $schoolId)
    {
        $names = collect($platform->products ?? [])->pluck('name_ar', 'code');
        $active = $this->subscribed($packageIds, $schoolId)->where('status', 'active')
            ->selectRaw('package_id, count(*) as n')->groupBy('package_id')->pluck('n', 'package_id');

        return HubPackage::withTrashed()->whereIn('id', $packageIds)
            ->with(['items' => fn ($q) => $q->where('platform_id', $platform->id)])->orderBy('sort')->orderBy('id')->get()
            ->each(function ($p) use ($names, $active) {
                $p->product_names = $p->items->map(fn ($i) => $names[$i->product_code] ?? $i->product_code)->all();
                $p->active_subscribers = (int) ($active[$p->id] ?? 0);
            });
    }

    private function subscribers(Request $request, HubPlatform $platform, array $packageIds, ?int $schoolId): array
    {
        $f = $request->validate([
            'package_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:'.implode(',', self::SUB_STATUSES)],
            'account_status' => ['nullable', 'in:pending,needs_info,active,conflict,failed,disabled,missing,unlinked,none'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $q = trim($f['q'] ?? '');
        $account = fn ($a) => $a->whereColumn('hub_platform_accounts.user_id', 'hub_subscriptions.user_id')
            ->where('platform_id', $platform->id)->where('role', 'student');

        $subs = $this->subscribed($packageIds, $schoolId)
            ->whereIn('status', self::SUB_STATUSES)
            ->when($f['package_id'] ?? null, fn ($s, $v) => $s->where('package_id', $v))
            ->when($f['status'] ?? null, fn ($s, $v) => $s->where('status', $v))
            ->when($f['account_status'] ?? null, fn ($s, $v) => $v === 'none'
                ? $s->whereNotExists(fn ($a) => $account($a->from('hub_platform_accounts')))
                : $s->whereExists(fn ($a) => $account($a->from('hub_platform_accounts'))->where('status', $v)))
            ->when($q !== '', fn ($s) => $s->whereHas('user', fn ($u) => $u->where(fn ($w) => $w
                ->where('name', 'like', '%'.$q.'%')->orWhere('national_id', 'like', $q.'%'))))
            ->with(['user.parents', 'package'])->orderByDesc('id')->paginate(25)->withQueryString();

        $accounts = HubPlatformAccount::where('platform_id', $platform->id)->where('role', 'student')
            ->whereIn('user_id', $subs->pluck('user_id'))->get()->keyBy('user_id');

        return ['subs' => $subs, 'accounts' => $accounts, 'filterPackages' => $this->packages($platform, $packageIds, $schoolId)];
    }

    private function students(Request $request, HubPlatform $platform)
    {
        $f = $request->validate(['linked' => ['nullable', 'in:0,1'], 'q' => ['nullable', 'string', 'max:100']]);
        $q = trim($f['q'] ?? '');

        return HubPlatformAccount::where('platform_id', $platform->id)->where('role', 'student')
            ->when(isset($f['linked']), fn ($s) => $f['linked'] === '1' ? $s->whereNotNull('user_id') : $s->whereNull('user_id'))
            // Digits search the indexed national_id / external_id by prefix; text searches the name.
            ->when($q !== '', fn ($s) => ctype_digit($q)
                ? $s->where(fn ($w) => $w->where('national_id', 'like', $q.'%')->orWhere('external_id', $q))
                : $s->where('name', 'like', '%'.$q.'%'))
            ->with('user:id,name')->orderByDesc('id')->paginate(30)->withQueryString();
    }
}
