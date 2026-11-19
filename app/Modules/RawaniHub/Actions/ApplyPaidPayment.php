<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
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

    public function __construct(
        private MoyasarGateway $gateway,
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private SubscriptionAccounts $subscriptionAccounts,
        private AccountSyncDispatcher $dispatcher,
    ) {}

    /** @return string applied | duplicate | rejected */
    public function execute(HubPayment $payment, string $moyasarPaymentId): string
    {
        $remote = $this->gateway->fetchPayment($moyasarPaymentId); // outside the transaction: no lock held over HTTP
        if (($remote['status'] ?? null) !== 'paid'
            || ! is_numeric($remote['amount'] ?? null) || (int) $remote['amount'] !== (int) $payment->amount
            || strtoupper((string) ($remote['currency'] ?? '')) !== strtoupper((string) $payment->currency)
            || ! $payment->moyasar_invoice_id || ($remote['invoice_id'] ?? null) !== $payment->moyasar_invoice_id) {
            return self::REJECTED;
        }

        return DB::transaction(function () use ($payment, $moyasarPaymentId) {
            $row = $this->payments->findForUpdate($payment->id);
            // failed = given up at 48h; a verified late payment still buys the service. paid/refunded never flip back.
            if (! $row || ! in_array($row->status, ['pending', 'failed'], true)) {
                return self::DUPLICATE;
            }
            $sub = $this->subscriptions->findForUpdate($row->subscription_id);
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
                'status' => 'active', 'starts_at' => $start,
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
