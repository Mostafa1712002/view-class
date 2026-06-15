<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\SmsServices\Actions\SendSmsBatchAction;
use App\Modules\SmsServices\Models\SmsMessage;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trello #240 — message reports / send logs.
 *
 * Reads ONLY the SMS channel (channel='sms') so it never touches the
 * parallel-edited WhatsApp/Mail tables. The channel column is designed so a
 * future union report can be added without schema change.
 */
class SmsReportsController extends Controller
{
    use HasSchoolScope;

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('messages.reports'), 403);
    }

    public function index(Request $request): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $messages = $this->query($request, $schoolId)
            ->with(['sender:id,name_ar,name_en', 'school:id,name', 'triggeredBy:id,name', 'batch:id'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.sms-services.reports.index', [
            'messages' => $messages,
            'filters'  => $request->only(['from', 'to', 'school_id', 'sender_id', 'status', 'recipient', 'sent_by']),
            'statuses' => SmsMessage::STATUSES,
            'senders'  => SmsSender::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'schools'  => auth()->user()?->isSuperAdmin()
                ? School::orderBy('name')->get(['id', 'name'])
                : collect(),
            'isAllSchools' => $schoolId === null,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $rows = $this->query($request, $schoolId)
            ->with(['sender', 'triggeredBy'])->orderByDesc('id')->limit(5000)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['القناة', 'اسم المرسل', 'المستلم', 'رقم الجوال', 'الدور', 'النص', 'الحالة', 'سبب الفشل', 'عدد الرسائل', 'الرصيد المخصوم', 'وقت الإرسال', 'المرسل', 'Batch'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $r = 2;
        foreach ($rows as $m) {
            $vals = [
                strtoupper($m->channel), $m->sender->name_ar ?? '', $m->recipient_name, $m->recipient,
                $m->recipient_role, $m->body, $m->statusLabel(), $m->error, $m->message_count,
                $m->credit_charged, optional($m->sent_at)?->format('Y-m-d H:i'), $m->triggeredBy->name ?? '', $m->batch_id,
            ];
            foreach ($vals as $i => $v) {
                $sheet->setCellValue([$i + 1, $r], $v);
            }
            $r++;
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'sms-report-' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $rows = $this->query($request, $schoolId)
            ->with(['sender', 'triggeredBy'])->orderByDesc('id')->limit(2000)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sms-services.reports.pdf', [
            'rows' => $rows,
        ]);

        return $pdf->download('sms-report-' . now()->format('Ymd_His') . '.pdf');
    }

    /** Re-queue a failed message (Trello #240: resend failed). */
    public function resend(Request $request, int $id, SendSmsBatchAction $action): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $msg = SmsMessage::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('channel', 'sms')
            ->whereIn('status', ['failed', 'no_credit', 'invalid_number'])
            ->findOrFail($id);

        $sender = $msg->sender_id ? SmsSender::find($msg->sender_id) : null;

        $action->execute(
            schoolId: $msg->school_id,
            senderUserId: auth()->id(),
            sender: $sender,
            templateId: $msg->template_id,
            body: $msg->body,
            recipients: [[
                'phone'   => $msg->recipient,
                'name'    => $msg->recipient_name,
                'role'    => $msg->recipient_role,
                'user_id' => $msg->recipient_user_id,
                'vars'    => [], // body already rendered
            ]],
            source: 'compose',
            name: 'إعادة إرسال رسالة فاشلة',
        );

        return back()->with('success', __('sms_reports.resent'));
    }

    private function query(Request $request, ?int $schoolId)
    {
        return SmsMessage::query()
            ->where('channel', 'sms')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            // super-admin all-schools may further filter to one school
            ->when($schoolId === null && $request->filled('school_id'),
                fn ($q) => $q->where('school_id', (int) $request->input('school_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('sender_id'), fn ($q) => $q->where('sender_id', (int) $request->input('sender_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('sent_by'), fn ($q) => $q->where('triggered_by', (int) $request->input('sent_by')))
            ->when($request->filled('recipient'), fn ($q) => $q->where(fn ($w) => $w
                ->where('recipient', 'like', '%' . $request->input('recipient') . '%')
                ->orWhere('recipient_name', 'like', '%' . $request->input('recipient') . '%')));
    }
}
