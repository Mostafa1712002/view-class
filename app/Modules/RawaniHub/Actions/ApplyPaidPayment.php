<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Modules\RawaniHub\Console\ReconcilePaymentsCommand;
use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\MoyasarGateway;
use App\Modules\RawaniHub\Services\SubscriptionAccounts;
use Illuminate\Support\Facades\DB;

/**
 * The one path that turns money into a subscription (design §6.4): webhook, return page, reconcile and admin «تحقق»
 * all end here. It trusts nothing it was told — the payment is re-fetched with the secret key and must be paid, for
 * the stored amount, currency and invoice. Applying twice changes nothing.
 */
final class ApplyPaidPayment
{
    public const APPLIED = 'applied';

    public const DUPLICATE = 'duplicate';

    public const REJECTED = 'rejected';

    /** Money arrived for a subscription that must not be (re)activated: payment recorded paid, subscription untouched. */
    public const NEEDS_REVIEW = 'needs_review';

    public function __construct(
        private MoyasarGateway $gateway,
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private SubscriptionAccounts $subscriptionAccounts,
        private AccountSyncDispatcher $dispatcher,
    ) {}

    /** @return string applied | duplicate | rejected | needs_review */
    public function execute(HubPayment $payment, string $moyasarPaymentId): string
    {
        $remote = $this->gateway->fetchPayment($moyasarPaymentId); // outside the transaction: no lock held over HTTP
        if (($remote['status'] ?? null) !== 'paid'
            || ! is_numeric($remote['amount'] ?? null) || (int) $remote['amount'] !== (int) $payment->amount
            || strtoupper((string) ($remote['currency'] ?? '')) !== strtoupper((string) $payment->currency)
            || ! $payment->moyasar_invoice_id || ($remote['invoice_id'] ?? null) !== $payment->moyasar_invoice_id) {
            // Money captured but not provably for this row: never applied, audited once so an admin can look.
            if (($remote['status'] ?? null) === 'paid'
                && ! ActivityLog::where(['action' => 'hub.payment.rejected', 'model_type' => HubPayment::class, 'model_id' => $payment->id])->exists()) {
                ActivityLog::log('hub.payment.rejected', "دفع Moyasar {$moyasarPaymentId} مدفوع لكنه لا يطابق المبلغ أو العملة أو الفاتورة للدفعة #{$payment->id}", $payment, null,
                    array_intersect_key($remote, array_flip(['id', 'status', 'amount', 'currency', 'invoice_id'])));
            }

            return self::REJECTED;
        }

        return DB::transaction(function () use ($payment, $moyasarPaymentId) {
            $row = $this->payments->findForUpdate($payment->id);
            // failed = given up at 48h; a verified late payment still buys the service. paid/refunded never flip back.
            if (! $row || ! in_array($row->status, ['pending', 'failed'], true)) {
                return self::DUPLICATE;
            }
            $sub = $this->subscriptions->findForUpdate($row->subscription_id);

            // Only a never-started purchase, or one reconcile gave up on as unpaid, may be activated. An admin-cancelled,
            // refunded, expired or active subscription is left alone; the paid row then shows in the payments admin
            // flag «مدفوعة بدون اشتراك فعّال» for a manual refund decision.
            $revivable = $sub->status === 'pending'
                || ($sub->status === 'cancelled' && $sub->note === ReconcilePaymentsCommand::GAVE_UP_NOTE);
            if (! $revivable) {
                $this->payments->update($row, ['status' => 'paid', 'moyasar_payment_id' => $moyasarPaymentId, 'paid_at' => now()]);
                ActivityLog::log('hub.payment.needs_review', "دفع Moyasar {$moyasarPaymentId} وصل لاشتراك #{$sub->id} حالته {$sub->status}؛ لم يُفعَّل ويحتاج مراجعة استرداد", $row, null, $row->toArray());

                return self::NEEDS_REVIEW;
            }
            $items = $sub->items ?? [];

            // Stack after the latest active window per item, as GrantPackage does.
            $start = now();
            foreach ($items as $item) {
                $end = $this->subscriptions->latestEndFor($sub->user_id, (int) $item['platform_id'], (string) $item['product_code']);
                if ($end && $end->greaterThan($start)) {
                    $start = $end;
                }
            }

            $this->payments->update($row, ['status' => 'paid', 'moyasar_payment_id' => $moyasarPaymentId, 'paid_at' => now()]);
            $this->subscriptions->update($sub, [
                'status' => 'active', 'starts_at' => $start, 'note' => $sub->status === 'cancelled' ? null : $sub->note,
                'ends_at' => $start->copy()->addDays((int) $sub->package->duration_days),
            ]);

            $ids = $this->subscriptionAccounts->forBeneficiary($sub->user_id, $items, create: true);
            $this->accounts->markDirty($ids);
            $this->dispatcher->afterCommit($ids);
            ActivityLog::log('hub.payment.paid', "دفع Moyasar {$moyasarPaymentId} فعّل الاشتراك #{$sub->id}", $row, null, $row->toArray());

            return self::APPLIED;
        });
    }
}
