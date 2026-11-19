<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\SubscriptionAccounts;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Revokes a pending/active subscription. It does not refund money (that is RecordRefund). */
final class CancelSubscription
{
    use RequiresSuperAdmin;

    public function __construct(
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private SubscriptionAccounts $subscriptionAccounts,
        private AccountSyncDispatcher $dispatcher,
        private PaymentRepository $payments,
    ) {}

    public function execute(User $actor, int $subscriptionId, ?string $note = null): HubSubscription
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $subscriptionId, $note) {
            // Lock order payment → subscription, the same as ApplyPaidPayment, so the two can't deadlock.
            $paymentId = $this->subscriptions->find($subscriptionId)?->payment?->id;
            $payment = $paymentId ? $this->payments->findForUpdate($paymentId) : null;
            $sub = $this->subscriptions->findForUpdate($subscriptionId);
            if (! $sub) {
                throw ValidationException::withMessages(['subscription' => 'not found']);
            }
            if (! in_array($sub->status, ['pending', 'active'], true)) {
                throw ValidationException::withMessages(['subscription' => "cannot cancel a {$sub->status} subscription"]);
            }
            $old = $sub->toArray();
            $sub = $this->subscriptions->update($sub, [
                'status' => 'cancelled',
                'note' => $note ? mb_substr(trim(($sub->note ? $sub->note.' | ' : '').$note), 0, 500) : $sub->note,
            ]);
            // An unpaid checkout stops here; if Moyasar still confirms it later, ApplyPaidPayment records the money
            // for refund review and leaves this cancelled subscription alone. (hub_payments has no `cancelled` status.)
            if ($payment && $payment->status === 'pending') {
                $this->payments->update($payment, ['status' => 'failed']);
            }
            $ids = $this->subscriptionAccounts->forBeneficiary($sub->user_id, $sub->items, create: false);
            $this->accounts->markDirty($ids);
            $this->dispatcher->afterCommit($ids);
            ActivityLog::log('hub.subscription.cancel', "إلغاء اشتراك #{$sub->id}", $sub, $old, $sub->toArray());

            return $sub;
        });
    }
}
