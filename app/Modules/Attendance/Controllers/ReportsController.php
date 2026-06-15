<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BehaviorRecord;
use App\Modules\Attendance\Services\AttendanceQueryService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #263 — Attendance / absence / behavior reports.
 *
 * Reads `attendances` + `behavior_records`. All queries are school-scoped
 * via the class->section join (attendance) or the school_id column (behavior).
 */
class ReportsController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly AttendanceQueryService $query) {}

    /** Report cards landing page. */
    public function index(): View
    {
        // touch scope so non-super-admin with null school is denied
        $this->scopedSchoolId();

        return view('admin.attendance.reports.index');
    }

    /** Attendance-status report (تقرير حالة الحضور). */
    public function attendanceStatus(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $classes  = $this->query->classesForScope($schoolId);
        $rows     = collect();

        if ($request->filled('date') || $request->filled('class_id')) {
            $rows = $this->scopedAttendance($schoolId)
                ->with(['student', 'classRoom'])
                ->when($request->filled('date'), fn ($q) => $q->whereDate('date', $request->date))
                ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', (int) $request->class_id))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->orderByDesc('date')->paginate(30)->withQueryString();
        }

        return view('admin.attendance.reports.attendance-status', compact('classes', 'rows'));
    }

    /** Day-absence report (تقرير غياب أيام). */
    public function dayAbsence(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $classes  = $this->query->classesForScope($schoolId);
        $rows     = collect();

        if ($request->filled('from') || $request->filled('class_id')) {
            $rows = $this->scopedAttendance($schoolId)
                ->with(['student', 'classRoom'])
                ->whereNull('period')
                ->where('status', 'absent')
                ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->from))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->to))
                ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', (int) $request->class_id))
                ->orderByDesc('date')->paginate(30)->withQueryString();
        }

        return view('admin.attendance.reports.day-absence', compact('classes', 'rows'));
    }

    /** Period-absence report (تقرير غياب حصص). */
    public function periodAbsence(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $classes  = $this->query->classesForScope($schoolId);
        $subjects = $this->query->subjectsForScope($schoolId);
        $rows     = collect();

        if ($request->filled('date') || $request->filled('class_id')) {
            $rows = $this->scopedAttendance($schoolId)
                ->with(['student', 'classRoom', 'subject'])
                ->whereNotNull('period')
                ->where('status', 'absent')
                ->when($request->filled('date'), fn ($q) => $q->whereDate('date', $request->date))
                ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', (int) $request->class_id))
                ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', (int) $request->subject_id))
                ->orderByDesc('date')->paginate(30)->withQueryString();
        }

        return view('admin.attendance.reports.period-absence', compact('classes', 'subjects', 'rows'));
    }

    /** Late report (تقرير التأخير). */
    public function late(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $classes  = $this->query->classesForScope($schoolId);
        $rows     = collect();

        if ($request->filled('from') || $request->filled('class_id')) {
            $type = $request->late_type; // late_day | late_period
            $rows = $this->scopedAttendance($schoolId)
                ->with(['student', 'classRoom'])
                ->where('status', 'late')
                ->when($type === 'late_day', fn ($q) => $q->whereNull('period'))
                ->when($type === 'late_period', fn ($q) => $q->whereNotNull('period'))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->from))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->to))
                ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', (int) $request->class_id))
                ->orderByDesc('date')->paginate(30)->withQueryString();
        }

        return view('admin.attendance.reports.late', compact('classes', 'rows'));
    }

    /** Aggregate absence report with chart data (الغياب المجمع). */
    public function aggregate(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $date     = $request->date ?: now()->format('Y-m-d');

        $base = $this->scopedAttendance($schoolId)->whereDate('date', $date)->whereNull('period');
        $totals = [
            'present' => (clone $base)->where('status', 'present')->count(),
            'absent'  => (clone $base)->where('status', 'absent')->count(),
            'late'    => (clone $base)->where('status', 'late')->count(),
            'excused' => (clone $base)->where('status', 'excused')->count(),
        ];
        $totals['all'] = array_sum($totals);

        // absence by class (chart)
        $byClass = $this->scopedAttendance($schoolId)
            ->whereDate('date', $date)->whereNull('period')->where('status', 'absent')
            ->with('classRoom')
            ->get()
            ->groupBy(fn ($r) => optional($r->classRoom)->name ?? '—')
            ->map->count();

        return view('admin.attendance.reports.aggregate', compact('totals', 'byClass', 'date'));
    }

    /** Behavior report (تقرير السلوك) — reads behavior_records. */
    public function behavior(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $rows     = collect();

        if ($request->filled('from') || $request->filled('name') || $request->boolean('show')) {
            $rows = BehaviorRecord::query()
                ->with(['subject', 'behavior', 'action', 'recorder'])
                ->where('scope', 'student')
                ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
                ->when($request->filled('name'), fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('name', 'like', '%'.$request->name.'%')))
                ->when($request->filled('national_id'), fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('national_id', 'like', '%'.$request->national_id.'%')))
                ->orderByDesc('created_at')->paginate(30)->withQueryString();
        }

        return view('admin.attendance.reports.behavior', compact('rows'));
    }

    /** Scope-aware base attendance query (school via class->section). */
    private function scopedAttendance(?int $schoolId): Builder
    {
        return Attendance::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->whereHas('classRoom.section', fn (Builder $s) => $s->where('school_id', $schoolId)));
    }
}
