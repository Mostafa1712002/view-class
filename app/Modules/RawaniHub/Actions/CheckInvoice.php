<?php

namespace App\Modules\RawaniHub\Actions;

use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Modules\RawaniHub\Services\MoyasarGateway;
use Throwable;

/**
 * Asks Moyasar about the stored invoice and applies a paid attempt (design §6.2). Shared by the return page, the
 * reconcile job and the admin «تحقق». Never reads the browser's query string. A gateway failure is reported and
 * answered as `error`, so a page renders and the reconcile loop moves on.
 */
final class CheckInvoice
{
    public const ERROR = 'error';

    public function __construct(
        private MoyasarGateway $gateway,
        private PaymentRepository $payments,
        private ApplyPaidPayment $apply,
    ) {}

    /** @return string applied | duplicate | rejected | error | no_invoice | the invoice status (initiated, expired, …) */
    public function execute(HubPayment $payment): string
    {
        if (! $payment->moyasar_invoice_id) {
            return 'no_invoice';
        }
        if ($payment->status === 'paid' || $payment->status === 'refunded') {
            return ApplyPaidPayment::DUPLICATE;
        }

        try {
            $invoice = $this->gateway->fetchInvoice($payment->moyasar_invoice_id);
            $this->payments->update($payment, ['last_checked_at' => now()]);
            // A failed attempt is not a failed invoice (the buyer may retry); any paid attempt is what counts.
            $paid = collect($invoice['payments'] ?? [])->first(fn ($p) => ($p['status'] ?? null) === 'paid' && ! empty($p['id']));

            return $paid ? $this->apply->execute($payment, (string) $paid['id']) : (string) ($invoice['status'] ?? 'unknown');
        } catch (Throwable $e) {
            report($e);

            return self::ERROR;
        }
    }
}
