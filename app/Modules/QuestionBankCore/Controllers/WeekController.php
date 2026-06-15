<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\ActivityLog;
use App\Modules\QuestionBankCore\Repositories\Contracts\WeekRepository;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #248 — Study weeks (الأسابيع الدراسية) management, scoped to a term. Weeks have
 * no school_id; they scope INDIRECTLY through academic_term_id → academic_year.
 * school_id. Every action resolves+validates the term against the caller's scope
 * via resolveTerm() (fail-closed) before any read/write. Reads gated by
 * canDo('weeks.view'); writes by weeks.create/edit/delete.
 */
class WeekController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private WeekRepository $weeks,
        private QbScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('weeks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $terms = $this->termsInScope($schoolId);
        $termId = (int) $request->get('term_id', optional($terms->first())->id);
        $term = $termId ? $this->resolveTerm($termId, $schoolId) : null;

        return view('admin.qb.taxonomy.weeks.index', [
            'terms'  => $terms,
            'term'   => $term,
            'termId' => $term?->id,
            'weeks'  => $term ? $this->weeks->listForTerm($term->id) : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('weeks.create'), 403);

        $data = $request->validate([
            'academic_term_id' => ['required', 'integer'],
            'sort_order'       => ['required', 'integer', 'min:1', 'max:60'],
            'name'             => ['required', 'string', 'max:255'],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $term = $this->resolveTerm((int) $data['academic_term_id'], $this->scopedSchoolId());

        if ($this->weeks->numberExists($term->id, (int) $data['sort_order'])) {
            return back()->withInput()->with('error', 'رقم الأسبوع مستخدم بالفعل في هذا الفصل.');
        }
        if ($this->weeks->dateOverlaps($term->id, $data['start_date'], $data['end_date'])) {
            return back()->withInput()->with('error', 'تواريخ الأسبوع تتداخل مع أسبوع آخر في نفس الفصل.');
        }

        $week = $this->weeks->create([
            'academic_term_id' => $term->id,
            'sort_order'       => (int) $data['sort_order'],
            'name'             => $data['name'],
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
        ]);
        ActivityLog::log('weeks.create', "إضافة أسبوع دراسي (#{$week->id})", $week);

        return redirect()->route('admin.qb.weeks.index', ['term_id' => $term->id])->with('success', 'تمت إضافة الأسبوع.');
    }

    public function update(Request $request, int $weekId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('weeks.edit'), 403);

        $week = $this->loadScoped($weekId);
        $data = $request->validate([
            'sort_order' => ['required', 'integer', 'min:1', 'max:60'],
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($this->weeks->numberExists($week->academic_term_id, (int) $data['sort_order'], $week->id)) {
            return back()->with('error', 'رقم الأسبوع مستخدم بالفعل في هذا الفصل.');
        }
        if ($this->weeks->dateOverlaps($week->academic_term_id, $data['start_date'], $data['end_date'], $week->id)) {
            return back()->with('error', 'تواريخ الأسبوع تتداخل مع أسبوع آخر في نفس الفصل.');
        }

        $this->weeks->update($week, [
            'sort_order' => (int) $data['sort_order'],
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
        ]);
        ActivityLog::log('weeks.edit', "تعديل أسبوع دراسي (#{$week->id})", $week);

        return redirect()->route('admin.qb.weeks.index', ['term_id' => $week->academic_term_id])->with('success', 'تم تحديث الأسبوع.');
    }

    public function destroy(int $weekId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('weeks.delete'), 403);

        $week = $this->loadScoped($weekId);
        $termId = $week->academic_term_id;
        $this->weeks->delete($week);
        ActivityLog::log('weeks.delete', "حذف أسبوع دراسي (#{$weekId})", null);

        return redirect()->route('admin.qb.weeks.index', ['term_id' => $termId])->with('success', 'تم حذف الأسبوع.');
    }

    /** Bulk-delete selected weeks (حذف المحدد). */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('weeks.delete'), 403);

        $data = $request->validate([
            'academic_term_id' => ['required', 'integer'],
            'ids'              => ['required', 'array'],
            'ids.*'           => ['integer'],
        ]);
        $term = $this->resolveTerm((int) $data['academic_term_id'], $this->scopedSchoolId());

        $deleted = $this->weeks->deleteMany($data['ids'], $term->id);
        ActivityLog::log('weeks.delete', "حذف جماعي لأسابيع ({$deleted})", null);

        return redirect()->route('admin.qb.weeks.index', ['term_id' => $term->id])->with('success', "تم حذف {$deleted} أسبوع.");
    }

    /** Bulk-create N sequential weeks (إدخال 19 أسبوع دفعة واحدة). */
    public function bulkStore(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('weeks.create'), 403);

        $data = $request->validate([
            'academic_term_id' => ['required', 'integer'],
            'count'            => ['required', 'integer', 'min:1', 'max:40'],
            'start_date'       => ['required', 'date'],
            'name_prefix'      => ['nullable', 'string', 'max:100'],
        ]);
        $term = $this->resolveTerm((int) $data['academic_term_id'], $this->scopedSchoolId());

        // Start numbering after the highest existing week number in the term.
        $existing = $this->weeks->listForTerm($term->id);
        $startNumber = (int) ($existing->max('sort_order') ?? 0) + 1;
        $prefix = $data['name_prefix'] ?: 'الأسبوع';
        $cursor = \Carbon\Carbon::parse($data['start_date'])->startOfDay();

        $now = now();
        $rows = [];
        for ($i = 0; $i < (int) $data['count']; $i++) {
            $num = $startNumber + $i;
            $start = $cursor->copy();
            $end = $cursor->copy()->addDays(6);
            $rows[] = [
                'academic_term_id' => $term->id,
                'sort_order'       => $num,
                'name'             => trim("{$prefix} {$num}"),
                'start_date'       => $start->toDateString(),
                'end_date'         => $end->toDateString(),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $cursor = $cursor->addDays(7);
        }
        $this->weeks->insertMany($rows);
        ActivityLog::log('weeks.create', "إدخال جماعي لأسابيع ({$data['count']})", null);

        return redirect()->route('admin.qb.weeks.index', ['term_id' => $term->id])
            ->with('success', "تمت إضافة {$data['count']} أسبوع.");
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /** Resolve a term within the caller's scope or 403. */
    private function resolveTerm(int $termId, ?int $schoolId): AcademicTerm
    {
        $term = AcademicTerm::query()
            ->whereKey($termId)
            ->when($schoolId !== null, fn ($q) => $q->whereHas('academicYear', fn ($w) => $w->where('school_id', $schoolId)))
            ->first();
        abort_if(! $term, 403, 'الفصل الدراسي خارج نطاقك.');

        return $term;
    }

    private function loadScoped(int $weekId): \App\Models\StudyWeek
    {
        $week = $this->weeks->find($weekId);
        abort_if(! $week, 404);
        // Confirm the week's term is in scope.
        $this->resolveTerm($week->academic_term_id, $this->scopedSchoolId());

        return $week;
    }

    /**
     * Academic terms the caller may manage weeks for (school-scoped).
     */
    private function termsInScope(?int $schoolId)
    {
        return AcademicTerm::query()
            ->when($schoolId !== null, fn ($q) => $q->whereHas('academicYear', fn ($w) => $w->where('school_id', $schoolId)))
            ->with('academicYear:id,name,school_id')
            ->orderByDesc('id')
            ->get(['id', 'name', 'academic_year_id']);
    }
}
