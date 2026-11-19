<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\CancelSubscription;
use App\Modules\RawaniHub\Actions\FindOrCreateBeneficiary;
use App\Modules\RawaniHub\Actions\GrantPackage;
use App\Modules\RawaniHub\Actions\NationalIdCollision;
use App\Modules\RawaniHub\DTOs\BeneficiaryData;
use App\Modules\RawaniHub\DTOs\GrantData;
use App\Modules\RawaniHub\Http\Requests\GrantRequest;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\HubPeople;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionsController extends Controller
{
    public function __construct(private SubscriptionRepository $subscriptions, private PackageRepository $packages, private HubPeople $people) {}

    public function index(Request $request)
    {
        return view('hub.admin.subscriptions.index', [
            'subscriptions' => $this->subscriptions->paginate($request->only(['package_id', 'status', 'source', 'from', 'to']))->withQueryString(),
            'packages' => $this->packages->paginate([], 200),
        ]);
    }

    public function grantForm(Request $request)
    {
        $parentId = trim((string) $request->query('parent_national_id'));

        return view('hub.admin.subscriptions.grant', [
            'packages' => $this->packages->paginate(['is_active' => true], 200),
            'parentNationalId' => $parentId,
            'children' => $parentId !== '' ? $this->people->childrenOfNationalId($parentId) : collect(),
        ]);
    }

    public function grant(GrantRequest $request, GrantPackage $grant, FindOrCreateBeneficiary $beneficiary)
    {
        $actor = $request->user();
        $userIds = array_map('intval', (array) $request->input('child_ids', []));

        if ($request->input('mode') === 'new') {
            try {
                $userIds[] = $beneficiary->execute(new BeneficiaryData(
                    name: $request->input('name'), gender: $request->input('gender'), nationalId: $request->input('national_id'),
                    stage: $request->input('stage'), grade: (int) $request->input('grade'),
                    parentName: $request->input('parent_name'), parentNationalId: $request->input('parent_national_id'), parentGender: $request->input('parent_gender'),
                ))->id;
            } catch (NationalIdCollision $e) {
                throw ValidationException::withMessages(['national_id' => $e->getMessage()]);
            }
        } else {
            $ids = preg_split('/[\s,]+/', trim((string) $request->input('national_ids')), -1, PREG_SPLIT_NO_EMPTY);
            $found = $this->people->byNationalIds($ids);
            if ($missing = array_diff($ids, $found->pluck('national_id')->all())) {
                throw ValidationException::withMessages(['national_ids' => __('hub_admin.unknown_national_ids', ['ids' => implode('، ', $missing)])]);
            }
            $userIds = array_merge($userIds, $found->pluck('id')->all());
        }

        $created = $grant->execute($actor, new GrantData(
            packageId: (int) $request->input('package_id'), userIds: $userIds,
            startsAt: $request->filled('starts_at') ? \Illuminate\Support\Carbon::parse($request->input('starts_at')) : null,
            note: $request->input('note'),
        ));

        return redirect()->route('admin.hub.subscriptions.index')->with('success', __('hub_admin.granted', ['count' => $created->count()]));
    }

    public function cancel(Request $request, int $subscription, CancelSubscription $cancel)
    {
        $cancel->execute($request->user(), $subscription, $request->validate(['note' => 'nullable|string|max:200'])['note'] ?? null);

        return back()->with('success', __('hub_admin.cancelled'));
    }
}
