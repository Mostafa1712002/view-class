<?php

namespace App\Modules\RawaniHub\Repositories\Contracts;

use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Models\HubPaymentEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepository
{
    public function find(int $id): ?HubPayment;

    public function findForUpdate(int $id): ?HubPayment;

    public function findByInvoiceId(string $invoiceId): ?HubPayment;

    public function findByMoyasarPaymentId(string $paymentId): ?HubPayment;

    /** Filters: status, flag (paid_without_active|unmatched). */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function create(array $attributes): HubPayment;

    public function update(HubPayment $payment, array $attributes): HubPayment;

    /** Webhook audit row; the caller strips secret_token from the payload. */
    public function recordEvent(array $attributes): HubPaymentEvent;
}
