<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WeeklyPlanNoteTemplate;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WeeklyPlanController extends Controller
{
    protected function getSchoolId()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? null : $user->school_id;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $this->getSchoolId();
        $viewMode = $request->get('view', 'grid'); // 'grid' (Sprint 5 default) or 'list' (legacy)

        // Resolve target week (Sun-Thu). A specific date filter pins the week to
        // the week that contains that date; otherwise week_start / current week.
        if ($request->filled('date')) {
            $weekStart = Carbon::parse($request->date)->startOfWeek(Carbon::SUNDAY);
        } elseif ($request->filled('week_start')) {
            $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::SUNDAY);
        } else {
            $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        }
        $weekEnd = $weekStart->copy()->addDays(4); // Thursday (school week)

        $query = WeeklyPlan::with(['teacher', 'subject', 'classRoom.section', 'lockedByUser']);

        // Teacher sees only their plans
        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            $query->where('teacher_id', $user->id);
        } elseif ($schoolId) {
            // School admin sees their school's plans
            $query->whereHas('teacher', fn($q) => $q->where('school_id', $schoolId));
        }

        // Filtering
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('grade_level')) {
            $query->whereHas('classRoom', fn($q) => $q->where('grade_level', $request->grade_level));
        }

        // Free-text search across plan content + teacher/subject names (auto-search).
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($w) use ($term) {
                foreach (['lesson_title', 'topics', 'objectives', 'homework', 'exams', 'notes'] as $col) {
                    $w->orWhere($col, 'like', "%{$term}%");
                }
                $w->orWhereHas('teacher', fn($t) => $t->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('subject', fn($s) => $s->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'locked') {
                $query->locked();
            } elseif ($request->status === 'unlocked') {
                $query->unlocked();
            } elseif ($request->status === 'prepared') {
                $query->where('is_prepared', true);
            } elseif ($request->status === 'not_prepared') {
                $query->where('is_prepared', false);
            }
        }

        // For filtering dropdowns
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();
        $classesQuery = ClassRoom::with('section');

        if ($schoolId) {
            $teachersQuery->where('school_id', $schoolId);
            $subjectsQuery->where('school_id', $schoolId);
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
        }

        $teachers = $teachersQuery->get();
        $subjects = $subjectsQuery->get();
        $classes = $classesQuery->get();
        $gradeLevels = $classes->pluck('grade_level')->filter()->unique()->sort()->values();

        if ($viewMode === 'grid') {
            // Grid view: scope to selected week, group by day-of-week
            $weekPlans = (clone $query)
                ->whereDate('week_start_date', $weekStart)
                ->get();

            // Card 66 — KPI tiles for the selected week
            $kpis = [
                'total' => $weekPlans->count(),
                'prepared' => $weekPlans->where('is_prepared', true)->count(),
                'not_prepared' => $weekPlans->where('is_prepared', false)->count(),
                'locked' => $weekPlans->where('is_locked', true)->count(),
            ];

            // Ready notes templates (small dropdown helper)
            $noteTemplatesQuery = WeeklyPlanNoteTemplate::query();
            if (!$user->isSuperAdmin()) {
                $noteTemplatesQuery->where(function ($w) use ($schoolId) {
                    $w->whereNull('school_id')->orWhere('school_id', $schoolId);
                });
            }
            $noteTemplates = $noteTemplatesQuery->latest()->limit(50)->get();

            return view('admin.weekly-plans.index-grid', compact(
                'weekPlans', 'weekStart', 'weekEnd',
                'teachers', 'subjects', 'classes', 'gradeLevels',
                'kpis', 'noteTemplates'
            ));
        }

        // Legacy list view
        if ($request->filled('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }
        $plans = $query->latest('week_start_date')->paginate(15);

        return view('admin.weekly-plans.index', compact('plans', 'teachers', 'subjects', 'classes', 'gradeLevels'));
    }

    /**
     * PDF export of the week grid.
     */
    public function pdf(Request $request)
    {
        [$weekPlans, $weekStart, $weekEnd] = $this->buildExportQuery($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.weekly-plans.pdf', [
            'weekPlans' => $weekPlans,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('weekly-plan-' . $weekStart->format('Y-m-d') . '.pdf');
    }

    /**
     * Excel (.xlsx) export of the filtered week using PhpSpreadsheet.
     */
    public function excel(Request $request)
    {
        [$weekPlans, $weekStart, $weekEnd] = $this->buildExportQuery($request);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('الخطة الأسبوعية');

        $headers = [
            'الحالة', 'المعلم', 'المادة', 'الفصل', 'الدرس',
            'الأهداف', 'الواجبات والمهام', 'الاختبارات', 'الملاحظات',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F8E8C1');

        $row = 2;
        foreach ($weekPlans as $plan) {
            $sheet->fromArray([
                $plan->is_locked ? 'مقفلة' : ($plan->is_prepared ? 'تم التحضير' : 'لم يتم التحضير'),
                $plan->teacher?->name ?? '',
                $plan->subject?->name ?? '',
                $plan->classRoom?->name ?? '',
                $plan->lesson_title ?? $plan->topics ?? '',
                $plan->objectives ?? '',
                $plan->homework ?? '',
                $plan->exams ?? '',
                $plan->notes ?? '',
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'weekly-plan-' . $weekStart->format('Y-m-d') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Shared filtered query for PDF + Excel exports.
     * Returns [Collection $weekPlans, Carbon $weekStart, Carbon $weekEnd].
     */
    protected function buildExportQuery(Request $request): array
    {
        $schoolId = $this->getSchoolId();

        if ($request->filled('date')) {
            $weekStart = Carbon::parse($request->date)->startOfWeek(Carbon::SUNDAY);
        } elseif ($request->filled('week_start')) {
            $weekStart = Carbon::parse($request->week_start)->startOfWeek(Carbon::SUNDAY);
        } else {
            $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        }
        $weekEnd = $weekStart->copy()->addDays(4);

        $query = WeeklyPlan::with(['teacher', 'subject', 'classRoom.section'])
            ->whereDate('week_start_date', $weekStart);

        if ($schoolId) {
            $query->whereHas('teacher', fn($q) => $q->where('school_id', $schoolId));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('grade_level')) {
            $query->whereHas('classRoom', fn($q) => $q->where('grade_level', $request->grade_level));
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($w) use ($term) {
                foreach (['lesson_title', 'topics', 'objectives', 'homework', 'exams', 'notes'] as $col) {
                    $w->orWhere($col, 'like', "%{$term}%");
                }
                $w->orWhereHas('teacher', fn($t) => $t->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('subject', fn($s) => $s->where('name', 'like', "%{$term}%"));
            });
        }

        return [$query->get(), $weekStart, $weekEnd];
    }

    /**
     * Mark a weekly plan as prepared (toggles is_prepared + prepared_at).
     */
    public function markPrepared(WeeklyPlan $weekly_plan)
    {
        if ($weekly_plan->is_prepared) {
            $weekly_plan->update(['is_prepared' => false, 'prepared_at' => null]);
            $msg = 'تم إلغاء حالة التحضير';
        } else {
            $weekly_plan->update(['is_prepared' => true, 'prepared_at' => now()]);
            $msg = 'تم تحديد الخطة كمُحضّرة';
        }
        return back()->with('success', $msg);
    }

    public function create()
    {
        $user = auth()->user();
        $schoolId = $this->getSchoolId();

        // Teacher can only create their own plans
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();
        $classesQuery = ClassRoom::with('section')->active();

        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            $teachers = collect([$user]);
            $subjectsQuery->whereHas('teachers', fn($q) => $q->where('users.id', $user->id));
        } else {
            if ($schoolId) {
                $teachersQuery->where('school_id', $schoolId);
                $subjectsQuery->where('school_id', $schoolId);
                $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
            }
            $teachers = $teachersQuery->get();
        }

        $subjects = $subjectsQuery->get();
        $classes = $classesQuery->get();

        // Calculate current and next week start dates
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $nextWeekStart = $currentWeekStart->copy()->addWeek();

        return view('admin.weekly-plans.create', compact('teachers', 'subjects', 'classes', 'currentWeekStart', 'nextWeekStart'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'week_start_date' => 'required|date',
            'objectives' => 'nullable|string',
            'topics' => 'nullable|string',
            'lesson_title' => 'nullable|string|max:255',
            'activities' => 'nullable|string',
            'resources' => 'nullable|string',
            'assessment' => 'nullable|string',
            'exams' => 'nullable|string',
            'homework' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'teacher_id.required' => 'المعلم مطلوب',
            'subject_id.required' => 'المادة مطلوبة',
            'class_id.required' => 'الفصل مطلوب',
            'week_start_date.required' => 'تاريخ بداية الأسبوع مطلوب',
        ]);

        // Check permission
        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            if ($validated['teacher_id'] != $user->id) {
                abort(403, 'لا يمكنك إنشاء خطة لمعلم آخر');
            }
        }

        // Calculate week end date
        $startDate = Carbon::parse($validated['week_start_date']);
        $validated['week_end_date'] = $startDate->copy()->addDays(6)->format('Y-m-d');

        // Check for duplicate
        $exists = WeeklyPlan::where('teacher_id', $validated['teacher_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('class_id', $validated['class_id'])
            ->where('week_start_date', $validated['week_start_date'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'توجد خطة بنفس البيانات مسبقاً');
        }

        $plan = WeeklyPlan::create($validated);

        return redirect()->route('manage.weekly-plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة الأسبوعية بنجاح');
    }

    public function show(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$this->canViewPlan($user, $weeklyPlan)) {
            abort(403);
        }

        $weeklyPlan->load(['teacher', 'subject', 'classRoom.section', 'lockedByUser']);

        return view('admin.weekly-plans.show', compact('weeklyPlan'));
    }

    public function edit(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$weeklyPlan->canEdit($user)) {
            return redirect()->route('manage.weekly-plans.show', $weeklyPlan)
                ->with('error', 'لا يمكن تعديل هذه الخطة - إما أنها مقفلة أو ليست لك');
        }

        $schoolId = $this->getSchoolId();

        $subjectsQuery = Subject::query();
        $classesQuery = ClassRoom::with('section')->active();

        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            $subjectsQuery->whereHas('teachers', fn($q) => $q->where('users.id', $user->id));
        } elseif ($schoolId) {
            $subjectsQuery->where('school_id', $schoolId);
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
        }

        $subjects = $subjectsQuery->get();
        $classes = $classesQuery->get();

        return view('admin.weekly-plans.edit', compact('weeklyPlan', 'subjects', 'classes'));
    }

    public function update(Request $request, WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$weeklyPlan->canEdit($user)) {
            return redirect()->route('manage.weekly-plans.show', $weeklyPlan)
                ->with('error', 'لا يمكن تعديل هذه الخطة');
        }

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'objectives' => 'nullable|string',
            'topics' => 'nullable|string',
            'lesson_title' => 'nullable|string|max:255',
            'activities' => 'nullable|string',
            'resources' => 'nullable|string',
            'assessment' => 'nullable|string',
            'exams' => 'nullable|string',
            'homework' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'subject_id.required' => 'المادة مطلوبة',
            'class_id.required' => 'الفصل مطلوب',
        ]);

        $weeklyPlan->update($validated);

        return redirect()->route('manage.weekly-plans.show', $weeklyPlan)
            ->with('success', 'تم تحديث الخطة الأسبوعية بنجاح');
    }

    public function destroy(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        // Only admins can delete, or teacher if plan is not locked
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            if ($weeklyPlan->teacher_id != $user->id || $weeklyPlan->is_locked) {
                abort(403);
            }
        }

        $weeklyPlan->delete();

        return redirect()->route('manage.weekly-plans.index')
            ->with('success', 'تم حذف الخطة الأسبوعية بنجاح');
    }

    public function lock(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        // Only admins can lock
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403, 'غير مصرح لك بقفل الخطط');
        }

        if ($weeklyPlan->is_locked) {
            return back()->with('info', 'الخطة مقفلة مسبقاً');
        }

        $weeklyPlan->lock($user);

        return back()->with('success', 'تم قفل الخطة بنجاح');
    }

    public function unlock(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        // Only admins can unlock
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403, 'غير مصرح لك بفتح قفل الخطط');
        }

        if (!$weeklyPlan->is_locked) {
            return back()->with('info', 'الخطة غير مقفلة');
        }

        $weeklyPlan->unlock();

        return back()->with('success', 'تم فتح قفل الخطة بنجاح');
    }

    public function bulkLock(Request $request)
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:weekly_plans,id',
        ]);

        $count = 0;
        foreach ($validated['plan_ids'] as $planId) {
            $plan = WeeklyPlan::find($planId);
            if ($plan && !$plan->is_locked) {
                $plan->lock($user);
                $count++;
            }
        }

        return back()->with('success', "تم قفل {$count} خطة بنجاح");
    }

    public function duplicate(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$this->canViewPlan($user, $weeklyPlan)) {
            abort(403);
        }

        // Calculate next week
        $nextWeekStart = Carbon::parse($weeklyPlan->week_start_date)->addWeek();
        $nextWeekEnd = $nextWeekStart->copy()->addDays(6);

        // Check for existing plan
        $exists = WeeklyPlan::where('teacher_id', $weeklyPlan->teacher_id)
            ->where('subject_id', $weeklyPlan->subject_id)
            ->where('class_id', $weeklyPlan->class_id)
            ->where('week_start_date', $nextWeekStart->format('Y-m-d'))
            ->exists();

        if ($exists) {
            return back()->with('error', 'توجد خطة للأسبوع القادم مسبقاً');
        }

        $newPlan = $weeklyPlan->replicate();
        $newPlan->week_start_date = $nextWeekStart;
        $newPlan->week_end_date = $nextWeekEnd;
        $newPlan->is_locked = false;
        $newPlan->locked_at = null;
        $newPlan->locked_by = null;
        $newPlan->save();

        return redirect()->route('manage.weekly-plans.edit', $newPlan)
            ->with('success', 'تم نسخ الخطة للأسبوع القادم بنجاح');
    }

    protected function canViewPlan(User $user, WeeklyPlan $plan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSchoolAdmin()) {
            return $plan->teacher->school_id === $user->school_id;
        }

        if ($user->isTeacher()) {
            return $plan->teacher_id === $user->id;
        }

        return false;
    }
}
