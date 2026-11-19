<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\RawaniHub\Actions\LinkPlatformAccount;
use App\Modules\RawaniHub\Actions\ResetAccountPassword;
use App\Modules\RawaniHub\Actions\RetryPlatformAccount;
use App\Modules\RawaniHub\Actions\SetAccountEnabled;
use App\Modules\RawaniHub\Http\Requests\LinkAccountRequest;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\HubPeople;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function __construct(private HubPeople $people, private PlatformAccountRepository $accounts, private SubscriptionRepository $subscriptions) {}

    public function show(int $user)
    {
        $person = $this->people->find($user);

        return view('hub.admin.people.show', [
            'person' => $person,
            'profile' => HubProfile::find($person->id),
            'accounts' => $this->accounts->forUser($person->id)->load('platform'),
            'subscriptions' => $this->subscriptions->paginate(['user_id' => $person->id], 50),
        ]);
    }

    public function retry(Request $request, int $account, RetryPlatformAccount $retry)
    {
        return $this->backTo($retry->execute($request->user(), $account), __('hub_admin.retry_queued'));
    }

    public function toggle(Request $request, int $account, SetAccountEnabled $set)
    {
        $row = $this->accounts->find($account) ?? abort(404);

        return $this->backTo($set->execute($request->user(), $account, ! $row->enabled), __('hub_admin.saved'));
    }

    public function resetPassword(Request $request, int $account, ResetAccountPassword $reset)
    {
        $row = $this->accounts->find($account) ?? abort(404);
        $password = $reset->execute($request->user(), $account);

        return $this->backTo($row, __('hub_admin.password_reset'))->with('shown_password', [$row->id, $password]);
    }

    /** Shows a managed account's password once; every reveal is audited. */
    public function reveal(int $account)
    {
        $row = $this->accounts->find($account) ?? abort(404);
        abort_unless($row->managed && $row->password, 404);
        ActivityLog::log('hub.account.reveal_password', "إظهار كلمة مرور الحساب #{$row->id}", $row);

        return $this->backTo($row)->with('shown_password', [$row->id, $row->password]);
    }

    public function link(LinkAccountRequest $request, int $account, LinkPlatformAccount $link)
    {
        $row = $link->execute($request->user(), $account, externalId: $request->validated('external_id'));

        return $this->backTo($row, __('hub_admin.linked'));
    }

    private function backTo($account, ?string $message = null)
    {
        $redirect = $account->user_id
            ? redirect()->route('admin.hub.people.show', $account->user_id)
            : back();

        return $message ? $redirect->with('success', $message) : $redirect;
    }
}
