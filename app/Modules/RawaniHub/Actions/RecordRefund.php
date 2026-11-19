<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\SubscriptionAccounts;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Records a refund the admin already made in the Moyasar dashboard (design §4.6); it moves no money. */
final class RecordRefund
{
    use RequiresSuperAdmin;

    public function __construct(
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private SubscriptionAccounts $subscriptionAccounts,
        private AccountSyncDispatcher $dispatcher,
    ) {}

    public function execute(User $actor, int $paymentId, string $note): HubPayment
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $paymentId, $note) {
            $payment = $this->payments->findForUpdate($paymentId);
            if (! $payment || $payment->status !== 'paid') {
                throw ValidationException::withMessages(['payment' => 'only a paid payment can be refunded']);
            }
            $old = $payment->toArray();
            $payment = $this->payments->update($payment, [
                'status' => 'refunded', 'refunded_at' => now(), 'refunded_by' => $actor->id, 'refund_note' => mb_substr(trim($note), 0, 500),
            ]);

            $sub = $this->subscriptions->findForUpdate($payment->subscription_id);
            if ($sub) {
                $sub = $this->subscriptions->update($sub, ['status' => 'refunded']);
                $ids = $this->subscriptionAccounts->forBeneficiary($sub->user_id, $sub->items, create: false);
                $this->accounts->markDirty($ids);
                $this->dispatcher->afterCommit($ids);
            }
            ActivityLog::log('hub.payment.refund', "تسجيل استرداد للدفعة #{$payment->id}", $payment, $old, $payment->toArray());

            return $payment;
        });
    }
}
