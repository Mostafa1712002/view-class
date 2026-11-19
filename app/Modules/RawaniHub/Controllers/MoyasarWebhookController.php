<?php

namespace App\Modules\RawaniHub\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\ApplyPaidPayment;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Moyasar → Rawani, server to server (design §6.3). Authenticated by the shared secret_token only; every authenticated
 * event is recorded (secret stripped) whatever its outcome. Activation itself never depends on this endpoint.
 */
class MoyasarWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentRepository $payments, ApplyPaidPayment $apply): JsonResponse
    {
        $expected = (string) config('moyasar.webhook_secret');
        $token = $request->input('secret_token');
        // An unset secret must not turn hash_equals('', '') into an open door.
        if ($expected === '' || ! is_string($token) || ! hash_equals($expected, $token)) {
            return ApiResponse::fail('UNAUTHORIZED', 'unauthorized', 401);
        }

        $event = $request->json()->all();
        unset($event['secret_token']);
        $type = (string) ($event['type'] ?? '');
        $data = is_array($event['data'] ?? null) ? $event['data'] : [];
        $paymentId = is_scalar($data['id'] ?? null) ? (string) $data['id'] : null;
        $site = is_scalar($data['metadata']['site'] ?? null) ? (string) $data['metadata']['site'] : null;
        $ours = (string) config('moyasar.site');

        try {
            $outcome = $this->handle($type, $data, $paymentId, $site, $ours, $payments, $apply);
        } catch (Throwable $e) {
            $this->record($payments, $type, $paymentId, $site, $event, 'error');

            throw $e; // 5xx: Moyasar retries; reconcile covers it regardless
        }
        $this->record($payments, $type, $paymentId, $site, $event, $outcome);

        return ApiResponse::ok(['outcome' => $outcome]);
    }

    private function handle(string $type, array $data, ?string $paymentId, ?string $site, string $ours, PaymentRepository $payments, ApplyPaidPayment $apply): string
    {
        // A foreign site tag (e.g. aafaq) is not ours. ponytail: an absent tag still matches by our own stored invoice id,
        // since whether invoice metadata is copied onto its payments is unverified; ApplyPaidPayment re-checks everything.
        if ($site !== null && $site !== $ours) {
            return 'ignored_site';
        }
        $invoiceId = is_scalar($data['invoice_id'] ?? null) ? (string) $data['invoice_id'] : null;
        $rawaniId = $site === $ours ? ($data['metadata']['rawani_payment_id'] ?? null) : null;
        $payment = ($invoiceId ? $payments->findByInvoiceId($invoiceId) : null)
            ?? (is_numeric($rawaniId) ? $payments->find((int) $rawaniId) : null);
        if (! $payment) {
            return 'unmatched';
        }
        if ($type !== 'payment_paid' || ! $paymentId) {
            return 'recorded'; // refunds and failures are only recorded in phase 1 (§6.6)
        }

        return $apply->execute($payment, $paymentId);
    }

    private function record(PaymentRepository $payments, string $type, ?string $paymentId, ?string $site, array $event, string $outcome): void
    {
        $payments->recordEvent([
            'event_type' => mb_substr($type !== '' ? $type : 'unknown', 0, 64),
            'moyasar_payment_id' => $paymentId !== null ? mb_substr($paymentId, 0, 64) : null,
            'site' => $site !== null ? mb_substr($site, 0, 32) : null,
            'payload' => $event,
            'outcome' => $outcome,
        ]);
    }
}
