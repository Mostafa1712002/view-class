<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsSenderAttachment;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Trello #243 (part 1) — tenant-facing sender-name request.
 *
 * Distinct from the legacy super-admin-managed SmsSenderRequestController which
 * is {school}-route-bound; this one is scoped via scopedSchoolId() so a school
 * admin manages their OWN sender-name requests. 7-state workflow + alerts(11) /
 * advertising(8) length rules.
 */
class SenderNameController extends Controller
{
    use HasSchoolScope;

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('messages.sender_name'), 403);
    }

    public function index(): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $senders = SmsSender::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('attachments')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.sms-services.sender-name.index', [
            'senders'  => $senders,
            'statuses' => SmsSender::STATUSES,
        ]);
    }

    public function create(): View
    {
        $this->gate();
        return view('admin.sms-services.sender-name.create', [
            'kinds' => SmsSender::KINDS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $kind = $request->input('kind', 'alerts');
        $max  = $kind === 'advertising' ? 8 : 11;

        $data = $request->validate([
            'kind'    => ['required', Rule::in(['alerts', 'advertising'])],
            'name_ar' => ['required', 'string', "max:{$max}", 'regex:/^[\x{0600}-\x{06FF}\s]+$/u'],
            'name_en' => ['required', 'string', "max:{$max}", 'regex:/^[A-Za-z0-9 \-]+$/'],
            'letter'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'stc_form'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'mobily_form'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'zain_form'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'commercial_reg'=> ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'action'        => ['nullable', 'in:draft,submit'],
        ], [
            'name_ar.regex' => __('sms_services.name_ar_regex'),
            'name_en.regex' => __('sms_services.name_en_regex'),
        ]);

        // prevent duplicate (same school + same arabic name not rejected)
        $dupe = SmsSender::where('school_id', $schoolId)
            ->where('name_ar', $data['name_ar'])
            ->whereNotIn('status', ['rejected'])
            ->exists();
        if ($dupe) {
            return back()->withInput()->with('error', __('sms_sender_name.duplicate'));
        }

        $sender = SmsSender::create([
            'school_id' => $schoolId,
            'name_ar'   => $data['name_ar'],
            'name_en'   => $data['name_en'],
            'kind'      => $data['kind'],
            'status'    => ($data['action'] ?? 'submit') === 'draft' ? 'draft' : 'submitted',
        ]);

        // store attachments keyed by provider/document
        $map = [
            'stc_form'    => 'stc',
            'mobily_form' => 'mobily',
            'zain_form'   => 'zain',
        ];
        foreach ($map as $field => $provider) {
            if ($request->hasFile($field)) {
                SmsSenderAttachment::create([
                    'sender_id' => $sender->id,
                    'provider'  => $provider,
                    'file_path' => $request->file($field)->store('sms-sender-attachments', 'public'),
                ]);
            }
        }
        // request letter + commercial reg stored as stc-bucketed generic docs
        // (attachment table only knows stc/mobily/zain enums; store under stc).
        foreach (['letter', 'commercial_reg'] as $field) {
            if ($request->hasFile($field)) {
                SmsSenderAttachment::create([
                    'sender_id' => $sender->id,
                    'provider'  => 'stc',
                    'file_path' => $request->file($field)->store('sms-sender-attachments', 'public'),
                ]);
            }
        }

        return redirect()->route('admin.sms.sender-name.index')
            ->with('success', __('sms_sender_name.submitted'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $sender = SmsSender::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['draft', 'needs_edit', 'rejected'])
            ->findOrFail($id);
        $sender->delete();

        return back()->with('success', __('common.deleted_successfully'));
    }

    /**
     * Super-admin approval: move a submitted/under-review request to active so
     * it becomes a usable sender (satisfies C8's "must have an active sender"
     * rule and C12's "موافقة الإدارة تعمل"). Not school-scoped — the platform
     * admin reviews requests across tenants.
     */
    public function approve(int $id): RedirectResponse
    {
        $this->gate();
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $sender = SmsSender::whereIn('status', ['submitted', 'under_review', 'needs_edit'])->findOrFail($id);
        $sender->update(['status' => 'active', 'rejection_reason' => null]);

        return back()->with('success', __('sms_sender_name.approved'));
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->gate();
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $sender = SmsSender::whereIn('status', ['submitted', 'under_review', 'needs_edit'])->findOrFail($id);
        $sender->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return back()->with('success', __('sms_sender_name.rejected'));
    }
}
