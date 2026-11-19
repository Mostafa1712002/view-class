<?php

namespace App\Modules\RawaniHub\Controllers\My;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\RawaniHub\Actions\AddChild;
use App\Modules\RawaniHub\Actions\CompleteBeneficiaryProfile;
use App\Modules\RawaniHub\Actions\NationalIdCollision;
use App\Modules\RawaniHub\Actions\CheckInvoice;
use App\Modules\RawaniHub\Actions\StartCheckout;
use App\Modules\RawaniHub\DTOs\BeneficiaryData;
use App\Modules\RawaniHub\Http\Requests\ChildRequest;
use App\Modules\RawaniHub\Http\Requests\CompleteProfileRequest;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\CheckoutReadiness;
use Illuminate\Http\Request;

/** «باقاتي»: a student sees and buys for themself, a parent for their own children only. */
class MyPackagesController extends Controller
{
    public function __construct(
        private PackageRepository $packages,
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private CheckoutReadiness $readiness,
    ) {}

    public function index(Request $request)
    {
        $me = $request->user();
        $people = $this->beneficiaries($me)->map(fn (User $u) => [
            'user' => $u,
            'subscriptions' => $this->subscriptions->paginate(['user_id' => $u->id], 50)->items(),
            'accounts' => $this->accounts->forUser($u->id)->load('platform'),
        ]);

        return view('hub.my.index', [
            'people' => $people,
            'me' => $me,
            'isParent' => $me->isParent(),
            // A parent's own platform accounts (e.g. Al-Awwal parent row).
            'ownAccounts' => $me->isParent() ? $this->accounts->forUser($me->id)->load('platform') : collect(),
            'packages' => $this->packages->purchasable(),
        ]);
    }

    public function buy(Request $request, int $package)
    {
        $pkg = $this->purchasable($package);
        $me = $request->user();
        $rows = $this->beneficiaries($me)->map(function (User $u) use ($pkg, $me) {
            $missing = $this->readiness->missing($pkg, $u);

            return ['user' => $u, 'missing' => $missing, 'ready' => $this->readiness->ready($missing), 'fixable' => $this->readiness->fixableBy($missing, $me, $u)];
        });

        return view('hub.my.buy', ['package' => $pkg, 'rows' => $rows, 'isParent' => $me->isParent()]);
    }

    public function checkout(Request $request, int $package)
    {
        $pkg = $this->purchasable($package);
        $me = $request->user();
        $beneficiary = $this->beneficiary($me, $request->integer('child_id'));

        if (! $this->readiness->ready($this->readiness->missing($pkg, $beneficiary))) {
            return redirect()->route('my.packages.buy', $pkg->id)->withErrors(['missing' => __('hub_my.complete_first')]);
        }

        // StartCheckout (Task 3.2): creates the pending subscription + payment and the hosted Moyasar invoice, returns its URL.
        $url = app(StartCheckout::class)->execute($me, $beneficiary, $pkg);

        return redirect()->away($url);
    }

    public function complete(CompleteProfileRequest $request, int $child, CompleteBeneficiaryProfile $complete)
    {
        $beneficiary = $this->beneficiary($request->user(), $child);
        try {
            $complete->execute($request->user(), $beneficiary, $request->validated());
        } catch (NationalIdCollision) {
            return back()->withErrors(['collision' => __('hub_my.contact_support')]);
        }

        return redirect()->route('my.packages.buy', $request->integer('package_id'))->with('success', __('hub_my.saved'));
    }

    public function childForm(Request $request)
    {
        abort_unless($request->user()->isParent(), 403);

        return view('hub.my.child');
    }

    public function childStore(ChildRequest $request, AddChild $add)
    {
        abort_unless($request->user()->isParent(), 403);
        $v = $request->validated();
        try {
            $add->execute($request->user(), new BeneficiaryData($v['name'], $v['gender'], $v['national_id'], $v['stage'], (int) $v['grade']));
        } catch (NationalIdCollision) {
            return back()->withInput()->withErrors(['collision' => __('hub_my.contact_support')]);
        }

        return redirect()->route('my.packages.index')->with('success', __('hub_my.child_added'));
    }

    public function return(Request $request, HubPayment $payment, CheckInvoice $check)
    {
        $this->authorizePayment($request->user(), $payment);
        // Design §6.2: ignore the query string, ask Moyasar about the stored invoice; applying is idempotent.
        if (in_array($payment->status, ['pending', 'failed'], true)) {
            $check->execute($payment);
            $payment->refresh();
        }
        $state = $this->state($payment);

        return view('hub.my.return', ['payment' => $payment, 'state' => $state, 'accounts' => $state['accounts'], 'canSee' => $this->canSee($request->user(), $payment->subscription->user_id)]);
    }

    /** JSON for the return page poll. Never carries credentials; the page reloads itself once everything settled. */
    public function status(Request $request, HubPayment $payment)
    {
        $this->authorizePayment($request->user(), $payment);
        $s = $this->state($payment);

        return response()->json(['payment' => $s['payment'], 'done' => $s['done'],
            'accounts' => $s['accounts']->map(fn ($a) => ['platform' => $a->platform?->name_ar, 'status' => $a->status, 'label' => __('hub_my.status_'.$a->status)])->values()]);
    }

    /** Statuses after which polling stops. */
    private const SETTLED = ['active', 'needs_info', 'conflict', 'failed', 'disabled'];

    private function state(HubPayment $payment): array
    {
        $sub = $payment->subscription;
        $platformIds = collect($sub->items)->pluck('platform_id')->all();
        $accounts = $this->accounts->forUser($sub->user_id)->where('role', 'student')
            ->filter(fn ($a) => in_array($a->platform_id, $platformIds))->load('platform')->values();
        $done = in_array($payment->status, ['failed', 'refunded'], true)
            || ($payment->status === 'paid' && $accounts->isNotEmpty() && $accounts->every(fn ($a) => in_array($a->status, self::SETTLED, true)));

        return ['payment' => $payment->status, 'done' => $done, 'accounts' => $accounts];
    }

    private function purchasable(int $id): HubPackage
    {
        $pkg = $this->packages->find($id);
        abort_unless($pkg && $pkg->is_active && $pkg->is_purchasable, 404);

        return $pkg;
    }

    /** @return \Illuminate\Support\Collection<int,User> a student: themself; a parent: their own children */
    private function beneficiaries(User $me)
    {
        return $me->isStudent() ? collect([$me]) : $me->children()->orderBy('users.name')->get();
    }

    /** The child must belong to the logged-in parent (403 otherwise); a student can only ever pick themself. */
    private function beneficiary(User $me, int $id): User
    {
        if ($me->isStudent()) {
            return $me;
        }
        $child = $id ? $me->children()->whereKey($id)->first() : null;
        abort_unless($child, 403);

        return $child;
    }

    private function canSee(User $me, int $ownerId): bool
    {
        return $me->id === $ownerId || $me->children()->whereKey($ownerId)->exists();
    }

    private function authorizePayment(User $me, HubPayment $payment): void
    {
        abort_unless($payment->buyer_id === $me->id || $this->canSee($me, $payment->subscription->user_id), 403);
    }
}
