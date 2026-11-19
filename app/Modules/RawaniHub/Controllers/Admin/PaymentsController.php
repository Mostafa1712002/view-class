<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\RecordRefund;
use App\Modules\RawaniHub\Models\HubPaymentEvent;
use App\Modules\RawaniHub\Repositories\Contracts\PaymentRepository;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function __construct(private PaymentRepository $payments) {}

    public function index(Request $request)
    {
        return view('hub.admin.payments.index', [
            'payments' => $this->payments->paginate($request->only(['status', 'flag']))->withQueryString(),
            'unmatched' => HubPaymentEvent::where('outcome', 'unmatched')->latest('id')->limit(20)->get(),
        ]);
    }

    public function refund(Request $request, int $payment, RecordRefund $refund)
    {
        $refund->execute($request->user(), $payment, $request->validate(['note' => 'required|string|max:500'])['note']);

        return back()->with('success', __('hub_payments.refunded'));
    }

    public function receipt(int $payment)
    {
        $p = $this->payments->find($payment);
        abort_unless($p && in_array($p->status, ['paid', 'refunded'], true), 404);

        return view('hub.admin.payments.receipt', ['payment' => $p->load(['subscription.package', 'subscription.user', 'buyer'])]);
    }
}
