<?php

namespace App\Modules\RawaniHub\Repositories;

use App\Modules\RawaniHub\Models\HubPayment;
use App\Modules\RawaniHub\Models\HubPaymentEvent;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPaymentRepository implements PaymentRepository
{
    public function find(int $id): ?HubPayment
    {
        return HubPayment::find($id);
    }

    public function findForUpdate(int $id): ?HubPayment
    {
        return HubPayment::whereKey($id)->lockForUpdate()->first();
    }

    public function findByInvoiceId(string $invoiceId): ?HubPayment
    {
        return HubPayment::where('moyasar_invoice_id', $invoiceId)->first();
    }

    public function findByMoyasarPaymentId(string $paymentId): ?HubPayment
    {
        return HubPayment::where('moyasar_payment_id', $paymentId)->first();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return HubPayment::with(['subscription.package', 'buyer'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['flag'] ?? null) === 'paid_without_active', fn ($q) => $q->where('status', 'paid')
                ->whereHas('subscription', fn ($s) => $s->where('status', '!=', 'active')))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $attributes): HubPayment
    {
        return HubPayment::create($attributes);
    }

    public function update(HubPayment $payment, array $attributes): HubPayment
    {
        $payment->update($attributes);

        return $payment;
    }

    public function recordEvent(array $attributes): HubPaymentEvent
    {
        return HubPaymentEvent::create($attributes);
    }
}
