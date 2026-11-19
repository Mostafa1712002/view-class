<?php

namespace App\Modules\RawaniHub\Console;

use App\Modules\RawaniHub\Actions\ApplyPaidPayment;
use App\Modules\RawaniHub\Actions\CheckInvoice;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Activation without the webhook (design §6.5): pending payments older than 10 minutes are checked through the invoice
 * API; one still unpaid after 48 hours is marked failed and its pending subscription cancelled. A verified payment
 * arriving later still applies (ApplyPaidPayment accepts a failed row).
 */
class ReconcilePaymentsCommand extends Command
{
    public const GIVE_UP_HOURS = 48;

    protected $signature = 'hub:reconcile-payments';

    protected $description = 'Check pending hub payments with Moyasar and give up on stale ones';

    public function handle(PaymentRepository $payments, SubscriptionRepository $subscriptions, CheckInvoice $check): int
    {
        $giveUpBefore = now()->subHours(self::GIVE_UP_HOURS);
        $counts = [];

        foreach ($payments->pendingCreatedBefore(now()->subMinutes(10)) as $payment) {
            $outcome = $check->execute($payment);
            // Gateway down: try again next run rather than fail a payment we couldn't see.
            if (! in_array($outcome, [ApplyPaidPayment::APPLIED, ApplyPaidPayment::DUPLICATE, CheckInvoice::ERROR], true)
                && $payment->created_at->lessThanOrEqualTo($giveUpBefore)) {
                $outcome = $this->giveUp($payment->id, $payments, $subscriptions) ? 'failed' : $outcome;
            }
            $counts[$outcome] = ($counts[$outcome] ?? 0) + 1;
        }

        $this->info($counts ? collect($counts)->map(fn ($n, $k) => "$k $n")->implode(', ') : 'nothing pending');

        return self::SUCCESS;
    }

    private function giveUp(int $paymentId, PaymentRepository $payments, SubscriptionRepository $subscriptions): bool
    {
        return DB::transaction(function () use ($paymentId, $payments, $subscriptions) {
            $row = $payments->findForUpdate($paymentId);
            if (! $row || $row->status !== 'pending') {
                return false; // applied meanwhile
            }
            $payments->update($row, ['status' => 'failed']);
            $sub = $subscriptions->findForUpdate($row->subscription_id);
            if ($sub && $sub->status === 'pending') {
                $subscriptions->update($sub, ['status' => 'cancelled']);
            }

            return true;
        });
    }
}
