<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SmsServices\Actions\SendSmsBatchAction;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsTemplate;
use App\Modules\SmsServices\Support\SmsSegmentCalculator;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Whatsapp\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trello #239 — send SMS from an Excel sheet.
 *
 * Flow: download template → upload xlsx → parse + preview (valid/invalid counts)
 * → confirm send. The parsed rows are stashed in the session between preview and
 * send so the file is parsed once.
 */
class SmsExcelController extends Controller
{
    use HasSchoolScope;

    /** Header keys the parser understands (Arabic + English). */
    private const COL_PHONE = ['رقم الجوال', 'الجوال', 'phone', 'mobile', 'number'];
    private const COL_NAME  = ['الاسم', 'name', 'student_name'];

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('messages.send_excel'), 403);
    }

    public function create(): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        return view('admin.sms-services.excel', [
            'templates' => SmsTemplate::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('is_active', true)->orderBy('title')->get(['id', 'title', 'body']),
            'senders'   => SmsSender::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereIn('status', ['accepted', 'active', 'approved'])
                ->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'preview'   => session('sms_excel_preview'),
        ]);
    }

    /** Download a blank xlsx template. */
    public function template(): StreamedResponse
    {
        $this->gate();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['رقم الجوال', 'الاسم', 'متغير 1', 'متغير 2', 'متغير 3'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $sheet->setCellValue('A2', '0500000000');
        $sheet->setCellValue('B2', 'اسم الطالب');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'sms-excel-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Upload + parse + validate; store rows in session, render preview. */
    public function preview(Request $request): RedirectResponse
    {
        $this->gate();
        $data = $request->validate([
            'file'        => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'template_id' => ['nullable', 'integer'],
            'sender_id'   => ['nullable', 'integer'],
            'body'        => ['required', 'string', 'max:1600'],
        ]);

        $rows = $this->parse($request->file('file')->getRealPath());

        $valid = $invalid = 0;
        $seen  = [];
        $out   = [];
        foreach ($rows as $r) {
            $norm   = PhoneNormalizer::normalize($r['phone'] ?? null);
            $body   = SmsTemplateRenderer::render($data['body'], $r['vars'], 'dash');
            $reason = null;
            if ($norm === null) {
                $status = 'no_number'; $reason = 'لا يوجد رقم';
            } elseif (! PhoneNormalizer::isValid($norm)) {
                $status = 'invalid_number'; $reason = 'رقم غير صحيح';
            } elseif (isset($seen[$norm])) {
                $status = 'duplicate'; $reason = 'رقم مكرر';
            } else {
                $status = 'valid'; $seen[$norm] = true;
            }
            $status === 'valid' ? $valid++ : $invalid++;

            $out[] = [
                'phone'   => $norm,
                'name'    => $r['name'],
                'vars'    => $r['vars'],
                'body'    => $body,
                'segments'=> SmsSegmentCalculator::segments($body),
                'status'  => $status,
                'reason'  => $reason,
            ];
        }

        session(['sms_excel_preview' => [
            'rows'        => $out,
            'total'       => count($out),
            'valid'       => $valid,
            'invalid'     => $invalid,
            'template_id' => $data['template_id'] ?? null,
            'sender_id'   => $data['sender_id'] ?? null,
            'body'        => $data['body'],
        ]]);

        return redirect()->route('admin.sms.excel.create')
            ->with('success', __('sms_excel.parsed', ['count' => count($out)]));
    }

    /** Confirm + send the previewed rows. */
    public function send(Request $request, SendSmsBatchAction $action): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $preview  = session('sms_excel_preview');

        if (! $preview || empty($preview['rows'])) {
            return redirect()->route('admin.sms.excel.create')
                ->with('error', __('sms_excel.no_preview'));
        }

        $sender = null;
        if (! empty($preview['sender_id'])) {
            $sender = SmsSender::where('school_id', $schoolId)
                ->whereIn('status', ['accepted', 'active', 'approved'])
                ->find($preview['sender_id']);
        }

        $templateId = null;
        if (! empty($preview['template_id'])) {
            $templateId = SmsTemplate::where('school_id', $schoolId)->find($preview['template_id'])?->id;
        }

        // only send rows with a usable number; the action re-applies skip rules.
        $rows = array_map(fn ($r) => [
            'phone'   => $r['phone'],
            'name'    => $r['name'],
            'role'    => null,
            'user_id' => null,
            'vars'    => $r['vars'],
        ], $preview['rows']);

        $batch = $action->execute(
            schoolId: $schoolId,
            senderUserId: auth()->id(),
            sender: $sender,
            templateId: $templateId,
            body: $preview['body'],
            recipients: $rows,
            source: 'excel',
            name: 'إرسال SMS من Excel',
        );

        session()->forget('sms_excel_preview');

        return redirect()->route('admin.sms.excel.create')
            ->with('success', __('sms_send.batch_done', [
                'sent'    => $batch->sent_count,
                'queued'  => $batch->queued_count,
                'failed'  => $batch->failed_count,
                'skipped' => $batch->skipped_count,
            ]));
    }

    public function clear(): RedirectResponse
    {
        session()->forget('sms_excel_preview');
        return redirect()->route('admin.sms.excel.create');
    }

    /**
     * @return array<int, array{phone:?string,name:?string,vars:array<string,mixed>}>
     */
    private function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, false, false);

        if (empty($matrix)) {
            return [];
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($matrix));
        $phoneIdx = $this->matchCol($header, self::COL_PHONE);
        $nameIdx  = $this->matchCol($header, self::COL_NAME);

        $rows = [];
        foreach ($matrix as $line) {
            // skip fully empty rows
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $phone = $phoneIdx !== null ? trim((string) ($line[$phoneIdx] ?? '')) : '';
            $name  = $nameIdx !== null ? trim((string) ($line[$nameIdx] ?? '')) : '';

            // build vars from all named columns (header => value) plus convenience keys
            $vars = [];
            foreach ($header as $i => $col) {
                if ($col === '') continue;
                $vars[$col] = trim((string) ($line[$i] ?? ''));
            }
            $vars['name'] = $name;
            $vars['student_name'] = $name;
            $vars['full_name'] = $name;
            $vars['first_name'] = $name !== '' ? (preg_split('/\s+/', $name)[0] ?? $name) : '';
            $vars['mobile'] = $phone;

            $rows[] = ['phone' => $phone ?: null, 'name' => $name ?: null, 'vars' => $vars];
        }

        return $rows;
    }

    private function matchCol(array $header, array $candidates): ?int
    {
        foreach ($header as $i => $h) {
            $h = mb_strtolower(trim((string) $h));
            foreach ($candidates as $c) {
                if ($h === mb_strtolower($c)) {
                    return $i;
                }
            }
        }
        return null;
    }
}
