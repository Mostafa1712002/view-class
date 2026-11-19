<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\User;
use App\Modules\RawaniHub\Console\ReconcilePaymentsCommand;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\CheckoutReadiness;
use App\Modules\RawaniHub\Services\MoyasarGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Self-checkout (design §6.1): a pending subscription + payment, then a hosted Moyasar invoice. Returns the invoice URL
 * to redirect to. Nothing is activated here; only ApplyPaidPayment does that once Moyasar confirms.
 */
final class StartCheckout
{
    public function __construct(
        private MoyasarGateway $gateway,
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private CheckoutReadiness $readiness,
    ) {}

    public function execute(User $buyer, User $beneficiary, HubPackage $package): string
    {
        if ($beneficiary->id !== $buyer->id && ! $buyer->children()->whereKey($beneficiary->id)->exists()) {
            throw new AuthorizationException;
        }
        // Prices are VAT-inclusive (decision Q8): the package price is exactly what is charged.
        $amount = (int) round((float) $package->price * 100);
        if (! $package->is_active || ! $package->is_purchasable || $amount < 100) {
            throw ValidationException::withMessages(['package' => 'package is not purchasable']);
        }
        if (! $this->readiness->ready($this->readiness->missing($package, $beneficiary))) {
            throw ValidationException::withMessages(['missing' => 'beneficiary data is incomplete']);
        }

        $items = $package->items->map(fn ($i) => ['platform_id' => $i->platform_id, 'product_code' => $i->product_code])->all();
        $currency = $package->currency ?: config('moyasar.currency', 'SAR');

        $payment = DB::transaction(function () use ($buyer, $beneficiary, $package, $items, $amount, $currency) {
            $sub = $this->subscriptions->create([
                'user_id' => $beneficiary->id, 'buyer_id' => $buyer->id, 'package_id' => $package->id, 'items' => $items,
                'price' => $package->price, 'source' => 'purchase', 'status' => 'pending',
            ]);

            return $this->payments->create([
                'subscription_id' => $sub->id, 'buyer_id' => $buyer->id, 'amount' => $amount, 'currency' => $currency, 'status' => 'pending',
            ]);
        });

        try {
            $invoice = $this->gateway->createInvoice([
                'amount' => $amount,
                'currency' => $currency,
                'description' => "باقة {$package->name_ar} — {$beneficiary->name}",
                // success_url is the browser redirect; callback_url would be a server POST we don't need (webhook + reconcile).
                'success_url' => route('my.packages.return', $payment->id),
                'back_url' => route('my.packages.index'),
                // Unpayable an hour before reconcile gives up, so an attempt still in 3DS can't complete on a failed row unnoticed.
                'expired_at' => now()->addHours(ReconcilePaymentsCommand::GIVE_UP_HOURS - 1)->toIso8601String(),
                // Only these two keys: never `email`/`app` (aafaq's webhook acts on them).
                'metadata' => ['site' => (string) config('moyasar.site'), 'rawani_payment_id' => (string) $payment->id],
            ]);
            if (empty($invoice['id']) || empty($invoice['url'])) {
                throw new \RuntimeException('Moyasar returned an invoice without id/url');
            }
        } catch (Throwable $e) {
            DB::transaction(function () use ($payment) {
                $this->payments->update($payment, ['status' => 'failed']);
                $this->subscriptions->update($payment->subscription, ['status' => 'cancelled']);
            });

            throw $e;
        }

        $this->payments->update($payment, ['moyasar_invoice_id' => $invoice['id']]);

        return (string) $invoice['url'];
    }
}
