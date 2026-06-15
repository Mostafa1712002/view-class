<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Models\SmsCreditLedger;
use App\Modules\SmsServices\Models\SmsCreditRechargeRequest;
use App\Modules\SmsServices\Services\CreditService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trello #243 (part 2) — SMS credit recharge requests + ledger.
 *
 * Schools submit a bank-transfer recharge request (with receipt). Credit is
 * only added to the balance once an admin approves it — approval writes a
 * ledger row via CreditService.
 */
class CreditController extends Controller
{
    use HasSchoolScope;

    /** Static bank-account info shown to schools (settings-driven later). */
    private const BANKS = [
        'alrajhi' => ['label' => 'مصرف الراجحي', 'iban' => 'SA00 0000 0000 0000 0000 0000', 'account' => '000000000000', 'name' => 'المنصة الذهبية'],
        'alahli'  => ['label' => 'البنك الأهلي',  'iban' => 'SA11 1111 1111 1111 1111 1111', 'account' => '111111111111', 'name' => 'المنصة الذهبية'],
    ];

    public function __construct(private readonly CreditService $credit) {}

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('messages.credit'), 403);
    }

    public function index(): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $setting = $schoolId
            ? SchoolSmsSetting::firstOrCreate(
                ['school_id' => $schoolId],
                ['is_active' => false, 'sms_used' => 0, 'sms_total' => 0, 'provider' => 'generic']
            )
            : null;

        $requests = SmsCreditRechargeRequest::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('reviewer:id,name')
            ->orderByDesc('id')->paginate(10);

        $ledger = SmsCreditLedger::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('id')->limit(20)->get();

        return view('admin.sms-services.credit.index', [
            'setting'   => $setting,
            'available' => $setting ? $this->credit->available($setting) : 0,
            'banks'     => self::BANKS,
            'requests'  => $requests,
            'ledger'    => $ledger,
            'statuses'  => SmsCreditRechargeRequest::STATUSES,
            'isSuperAdmin' => auth()->user()?->isSuperAdmin() ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'bank_name'         => ['required', 'string', 'max:120'],
            'amount_transferred'=> ['required', 'numeric', 'min:1'],
            'transfer_date'     => ['required', 'date'],
            'from_bank'         => ['nullable', 'string', 'max:120'],
            'from_account_no'   => ['nullable', 'string', 'max:60'],
            'from_account_name' => ['nullable', 'string', 'max:120'],
            'granted_credit'    => ['nullable', 'integer', 'min:0'],
            'receipt'           => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $recharge = SmsCreditRechargeRequest::create([
            'school_id'          => $schoolId,
            'requested_by'       => auth()->id(),
            'bank_name'          => $data['bank_name'],
            'amount_transferred' => $data['amount_transferred'],
            'transfer_date'      => $data['transfer_date'],
            'from_bank'          => $data['from_bank'] ?? null,
            'from_account_no'    => $data['from_account_no'] ?? null,
            'from_account_name'  => $data['from_account_name'] ?? null,
            'granted_credit'     => $data['granted_credit'] ?? 0,
            'receipt_path'       => $request->file('receipt')->store('sms-credit-receipts', 'public'),
            'status'             => 'pending',
        ]);

        ActivityLog::logCreate($recharge, 'طلب شحن رصيد رسائل: '.$recharge->amount_transferred);

        return redirect()->route('admin.sms.credit.index')
            ->with('success', __('sms_credit.request_submitted'));
    }

    /** Approve a recharge request — only here is credit actually added. */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $this->gate();
        // approval is an admin action; super-admin only (school can't approve own)
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $req = SmsCreditRechargeRequest::where('status', 'pending')->findOrFail($id);
        $data = $request->validate([
            'granted_credit' => ['required', 'integer', 'min:1'],
            'admin_note'     => ['nullable', 'string', 'max:500'],
        ]);

        $setting = SchoolSmsSetting::firstOrCreate(
            ['school_id' => $req->school_id],
            ['is_active' => false, 'sms_used' => 0, 'sms_total' => 0, 'provider' => 'generic']
        );

        $this->credit->recharge(
            $setting,
            $data['granted_credit'],
            'شحن رصيد — طلب #' . $req->id,
            auth()->id(),
            'sms_credit_recharge_requests',
            $req->id
        );

        $req->update([
            'status'         => 'approved',
            'granted_credit' => $data['granted_credit'],
            'admin_note'     => $data['admin_note'] ?? null,
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
        ]);

        return back()->with('success', __('sms_credit.approved'));
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->gate();
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $req = SmsCreditRechargeRequest::where('status', 'pending')->findOrFail($id);
        $req->update([
            'status'      => 'rejected',
            'admin_note'  => $request->input('admin_note'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('sms_credit.rejected'));
    }
}
