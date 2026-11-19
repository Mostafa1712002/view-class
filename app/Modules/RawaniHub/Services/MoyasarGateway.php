<?php

namespace App\Modules\RawaniHub\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Moyasar REST calls (hosted invoices; design §6). No SDK: Basic auth with the secret key of the configured mode.
 * Every call throws on a non-2xx answer (Illuminate RequestException) so callers never act on a half response.
 */
class MoyasarGateway
{
    /** aafaq's webhook acts on these metadata keys; we share its Moyasar account, so they must never leave Rawani. */
    public const FORBIDDEN_METADATA = ['email', 'app'];

    /** @param array{amount:int, currency:string, description:string, metadata:array} $payload */
    public function createInvoice(array $payload): array
    {
        $leaked = array_intersect(array_map('strtolower', array_keys($payload['metadata'] ?? [])), self::FORBIDDEN_METADATA);
        if ($leaked !== []) {
            throw new InvalidArgumentException('Moyasar metadata must not carry: '.implode(', ', $leaked));
        }

        return $this->http()->post('invoices', $payload)->throw()->json();
    }

    public function fetchInvoice(string $id): array
    {
        return $this->http()->get('invoices/'.rawurlencode($id))->throw()->json();
    }

    public function fetchPayment(string $id): array
    {
        return $this->http()->get('payments/'.rawurlencode($id))->throw()->json();
    }

    private function http(): PendingRequest
    {
        $mode = (string) config('moyasar.mode');
        $secret = config("moyasar.keys.$mode.secret");
        if (! $secret) {
            throw new RuntimeException("Moyasar secret key is not configured for mode [$mode].");
        }

        return Http::withBasicAuth($secret, '')->baseUrl(rtrim((string) config('moyasar.api_url'), '/'))
            ->acceptJson()->asJson()->timeout(20);
    }
}
