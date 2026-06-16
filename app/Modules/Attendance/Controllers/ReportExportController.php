<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\School;
use App\Modules\Attendance\Controllers\Concerns\ExportsReports;
use App\Modules\Attendance\Services\AttendanceQueryService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trello #273 — export / print / PDF for the Sprint-10 attendance reports
 * (#263). Reuses the exact scoped + filtered query the report screen renders
 * (AttendanceQueryService::reportQuery) so the export matches the data on
 * screen, respects the active filters, and never leaks data outside the
 * caller's school scope.
 *
 * Gating: super-admin always; others need the export permission (fail-closed
 * via canDo, since 'pdf_export' / 'attendance.export' are not '.view' keys).
 */
class ReportExportController extends Controller
{
    use HasSchoolScope;
    use ExportsReports;

    /** status label maps shared with the on-screen badges. */
    private const STATUS = ['present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر', 'excused' => 'بعذر'];

    /** Human report titles (used in PDF header + file names). */
    private const TITLES = [
        'status'         => 'تقرير حالة الحضور',
        'day-absence'    => 'تقرير غياب الأيام',
        'period-absence' => 'تقرير غياب الحصص',
        'late'           => 'تقرير التأخير',
        'behavior'       => 'تقرير السلوك',
    ];

    public function __construct(private readonly AttendanceQueryService $query) {}

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('pdf_export'), 403);
    }

    /**
     * Single entry: /admin/attendance/reports/export/{report}?format=pdf|excel|csv
     */
    public function export(Request $request, string $report)
    {
        $this->gate();
        abort_unless(array_key_exists($report, self::TITLES), 404);

        $schoolId = $this->scopedSchoolId(); // null = super-admin see-all
        $format   = in_array($request->get('format'), ['pdf', 'excel', 'csv'], true)
            ? $request->get('format') : 'pdf';

        [$headers, $rows, $records] = $report === 'behavior'
            ? $this->behaviorData($schoolId, $request)
            : $this->attendanceData($report, $schoolId, $request);

        $title    = self::TITLES[$report];
        $filename = $report . '-' . now()->format('Ymd_His');

        if ($format === 'csv') {
            return $this->exportCsv($headers, $rows, $filename . '.csv');
        }
        if ($format === 'excel') {
            return $this->exportExcel($headers, $rows, $filename . '.xlsx');
        }

        // PDF: render a generic table view with branding partials.
        $school = $schoolId ? School::find($schoolId) : null;

        return $this->exportPdf('admin.attendance.reports.pdf.table', [
            'pdf_title'  => $title,
            'pdf_school' => $school?->name ?? 'كل المدارس',
            'pdf_date'   => $this->filterSummary($request),
            'headers'    => $headers,
            'rows'       => $rows,
        ], $filename . '.pdf');
    }

    /**
     * @return array{0:array<int,string>,1:array<int,array<int,string>>,2:mixed}
     */
    private function attendanceData(string $report, ?int $schoolId, Request $request): array
    {
        $records = $this->query
            ->reportQuery($report, $schoolId, $request->all())
            ->limit(5000)
            ->get();

        $headers = ['#', 'الطالب', 'الفصل', 'المادة', 'التاريخ', 'الحالة', 'الحصة', 'ملاحظة'];
        $rows = $records->values()->map(function ($r, $i) {
            return [
                $i + 1,
                optional($r->student)->name ?? '—',
                optional($r->classRoom)->name ?? '—',
                optional($r->subject)->name ?? '—',
                optional($r->date)->format('Y-m-d') ?? '—',
                self::STATUS[$r->status] ?? $r->status,
                $r->period ?? '—',
                $r->notes ?? '',
            ];
        })->all();

        return [$headers, $rows, $records];
    }

    /**
     * Behavior report (reads behavior_records, school_id column).
     *
     * @return array{0:array<int,string>,1:array<int,array<int,string>>,2:mixed}
     */
    private function behaviorData(?int $schoolId, Request $request): array
    {
        $records = BehaviorRecord::query()
            ->with(['subject', 'behavior', 'action', 'recorder'])
            ->where('scope', 'student')
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('name'), fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('name', 'like', '%'.$request->name.'%')))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $headers = ['#', 'الطالب', 'السلوك', 'الإجراء', 'بواسطة', 'التاريخ'];
        $rows = $records->values()->map(fn ($r, $i) => [
            $i + 1,
            optional($r->subject)->name ?? '—',
            optional($r->behavior)->name ?? '—',
            optional($r->action)->name ?? '—',
            optional($r->recorder)->name ?? '—',
            optional($r->created_at)->format('Y-m-d') ?? '—',
        ])->all();

        return [$headers, $rows, $records];
    }

    /** A short "filters used" line for the PDF header (card requirement). */
    private function filterSummary(Request $request): string
    {
        $parts = [];
        if ($request->filled('date')) $parts[] = 'التاريخ: ' . $request->date;
        if ($request->filled('from')) $parts[] = 'من: ' . $request->from;
        if ($request->filled('to'))   $parts[] = 'إلى: ' . $request->to;
        if ($request->filled('status')) $parts[] = 'الحالة: ' . (self::STATUS[$request->status] ?? $request->status);

        return ($parts ? implode(' · ', $parts) . ' — ' : '') . now()->format('Y-m-d H:i');
    }
}
