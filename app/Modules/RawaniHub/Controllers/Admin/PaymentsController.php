<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\CheckInvoice;
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

    /** «تحقق»: re-fetch the invoice from Moyasar and apply a paid attempt (same path as the return page). */
    public function check(int $payment, CheckInvoice $check)
    {
        $p = $this->payments->find($payment);
        abort_unless($p, 404);
        $outcome = $check->execute($p);

        return back()->with($outcome === CheckInvoice::ERROR ? 'error' : 'success', __('hub_payments.checked', ['outcome' => $outcome]));
    }

    public function receipt(int $payment)
    {
        $p = $this->payments->find($payment);
        abort_unless($p && in_array($p->status, ['paid', 'refunded'], true), 404);

        return view('hub.admin.payments.receipt', ['payment' => $p->load(['subscription.package', 'subscription.user', 'buyer'])]);
    }
}
