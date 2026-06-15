<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBankCore\Models\QbExam;
use App\Modules\QuestionBankCore\Models\QbExamQuestion;
use App\Modules\QuestionBankCore\Models\QbExamTarget;
use App\Modules\QuestionBankCore\Repositories\Contracts\QuestionRepository;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * #255 — electronic & paper exams built from the question bank. Module-owned
 * (qb_exams/*) so the legacy `exams` feature and the #217 bankPicker/addFromBank
 * are untouched.
 *
 * Gating: reads → canDo('exams.view'); writes → canDo('exams.create'|'exams.edit'|
 * 'exams.delete') (all fail closed). The picker additionally needs
 * canDo('question_banks.view'). scopedSchoolId() (fail-closed) scopes every query.
 */
class ExamController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private QuestionRepository $questions,
        private QbScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('exams.view'), 403);

        $schoolId = $this->scopedSchoolId();

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'subject_id' => $request->get('subject_id'),
            'semester_id' => $request->get('semester_id'),
            'delivery_type' => $request->get('delivery_type'),
            'grade_level' => $request->get('grade_level'),
            'status' => $request->get('status'),
        ];

        $query = QbExam::query()
            ->with(['subject:id,name', 'targets'])
            ->withCount('questions')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId));

        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if ($filters['subject_id']) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }
        if ($filters['semester_id']) {
            $query->where('semester_id', (int) $filters['semester_id']);
        }
        if ($filters['delivery_type']) {
            $query->where('delivery_type', $filters['delivery_type']);
        }
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['grade_level']) {
            $query->whereHas('targets', fn ($t) => $t->where('grade_level', (int) $filters['grade_level']));
        }

        $exams = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.qb.exams.index', [
            'exams' => $exams,
            'filters' => $filters,
            'subjects' => $this->scope->subjectsForSchool($schoolId),
            'semesters' => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()->canDo('exams.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $delivery = $request->get('delivery_type', QbExam::DELIVERY_ELECTRONIC);
        if (! in_array($delivery, [QbExam::DELIVERY_ELECTRONIC, QbExam::DELIVERY_PAPER], true)) {
            $delivery = QbExam::DELIVERY_ELECTRONIC;
        }

        return view('admin.qb.exams.create', [
            'delivery' => $delivery,
            'subjects' => $this->scope->subjectsForSchool($schoolId),
            'semesters' => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
            'tree' => $this->scope->schoolTree($schoolId),
            'schoolId' => $schoolId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('exams.create'), 403);

        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'delivery_type' => ['required', 'in:electronic,paper'],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')->where(
                fn ($q) => $schoolId
                    ? $q->where(fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id'))
                    : $q
            )],
            'semester_id' => ['nullable', 'integer', Rule::exists('academic_terms', 'id')->where(
                fn ($q) => $schoolId
                    ? $q->whereIn('academic_year_id', DB::table('academic_years')->where('school_id', $schoolId)->select('id'))
                    : $q
            )],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'selection_strategy' => ['nullable', 'in:manual,random'],
            'questions_target' => ['nullable', 'integer', 'min:1'],
            'pass_score' => ['nullable', 'numeric', 'min:0'],
            'grade_levels' => ['nullable', 'array'],
            'grade_levels.*' => ['nullable', 'integer'],
        ]);

        // For a scoped admin the exam belongs to their school; a super-admin's exam
        // is company-wide unless they target specific schools.
        $exam = QbExam::create([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'delivery_type' => $data['delivery_type'],
            'subject_id' => $data['subject_id'] ?? null,
            'semester_id' => $data['semester_id'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'selection_strategy' => $data['selection_strategy'] ?? 'manual',
            'questions_target' => $data['questions_target'] ?? null,
            'pass_score' => $data['pass_score'] ?? null,
            'allow_direct_access' => $request->boolean('allow_direct_access'),
            'show_result_immediately' => $request->boolean('show_result_immediately'),
            'allow_retake' => $request->boolean('allow_retake'),
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_answers' => $request->boolean('shuffle_answers'),
            'status' => QbExam::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        foreach (array_filter($data['grade_levels'] ?? []) as $gl) {
            QbExamTarget::create([
                'qb_exam_id' => $exam->id,
                'school_id' => $schoolId,
                'grade_level' => (int) $gl,
            ]);
        }

        ActivityLog::log('exams.create', "إنشاء اختبار ({$exam->delivery_type}) #{$exam->id}", $exam);

        return redirect()->route('admin.qb.exams.picker', $exam->id)
            ->with('success', 'تم إنشاء الاختبار. اختر الأسئلة من بنك الأسئلة.');
    }

    public function show(int $examId): View
    {
        abort_unless(auth()->user()->canDo('exams.view'), 403);

        $exam = $this->loadScopedExam($examId, ['questions', 'targets', 'subject']);

        return view('admin.qb.exams.show', compact('exam'));
    }

    public function results(int $examId): View
    {
        abort_unless(auth()->user()->canDo('exams.view'), 403);

        $exam = $this->loadScopedExam($examId, ['questions']);

        // Electronic exam attempts run through the legacy StudentExam engine, which
        // is NOT wired to qb_exams (out of #255 acceptance). This is a basic results
        // placeholder — see the spec real-vs-stub note.
        return view('admin.qb.exams.results', compact('exam'));
    }

    public function publish(int $examId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('exams.edit'), 403);

        $exam = $this->loadScopedExam($examId);
        if ($exam->questions()->count() === 0) {
            return back()->with('error', 'لا يمكن نشر اختبار بدون أسئلة.');
        }
        $exam->update(['status' => QbExam::STATUS_PUBLISHED, 'is_published' => true]);
        ActivityLog::log('exams.edit', "نشر اختبار #{$exam->id}", $exam);

        return back()->with('success', 'تم نشر الاختبار.');
    }

    public function unpublish(int $examId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('exams.edit'), 403);

        $exam = $this->loadScopedExam($examId);
        $exam->update(['status' => QbExam::STATUS_STOPPED, 'is_published' => false]);
        ActivityLog::log('exams.edit', "إيقاف اختبار #{$exam->id}", $exam);

        return back()->with('success', 'تم إيقاف الاختبار.');
    }

    public function destroy(int $examId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('exams.delete'), 403);

        $exam = $this->loadScopedExam($examId);
        ActivityLog::log('exams.delete', "حذف اختبار #{$exam->id}", $exam);
        $exam->delete();

        return redirect()->route('admin.qb.exams.index')->with('success', 'تم حذف الاختبار.');
    }

    // ── bank-question picker (snapshot) ──────────────────────────────────────

    public function picker(Request $request, int $examId): View
    {
        abort_unless(auth()->user()->canDo('exams.edit') && auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $exam = $this->loadScopedExam($examId, ['questions']);

        $filters = [
            'bank_id' => $request->get('bank_id'),
            'subject_id' => $request->get('subject_id'),
            'grade_id' => $request->get('grade_id'),
            'semester_id' => $request->get('semester_id'),
            'week_id' => $request->get('week_id'),
            'skill_id' => $request->get('skill_id'),
            'difficulty' => $request->get('difficulty'),
            'type' => $request->get('type'),
            'category' => $request->get('category'),
            'code' => $request->get('code'),
            // Picker shows ONLY approved questions (bank policy / card requirement).
            'status' => 'approved',
        ];

        $bankQuestions = $this->questions->paginate($schoolId, $filters, 25);

        $alreadyIds = $exam->questions->pluck('bank_question_id')->filter()->all();

        return view('admin.qb.exams.picker', [
            'exam' => $exam,
            'bankQuestions' => $bankQuestions,
            'banks' => $this->questions->banksForScope($schoolId),
            'subjects' => $this->scope->subjectsForSchool($schoolId),
            'skills' => $this->scope->skillsForSchool($schoolId),
            'alreadyIds' => $alreadyIds,
            'filters' => $filters,
        ]);
    }

    public function addFromBank(Request $request, int $examId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('exams.edit') && auth()->user()->canDo('question_banks.view'), 403);

        $request->validate([
            'bank_question_ids' => ['required', 'array', 'min:1'],
            'bank_question_ids.*' => ['required', 'integer'],
        ]);

        $schoolId = $this->scopedSchoolId();
        $exam = $this->loadScopedExam($examId, ['questions']);

        $existing = $exam->questions->pluck('bank_question_id')->filter()->all();
        $nextOrder = (int) ($exam->questions()->max('sort_order') ?? 0) + 1;
        $added = 0;

        foreach ($request->input('bank_question_ids') as $bqId) {
            // Re-scope each id and require APPROVED — never trust the posted list.
            $bq = $this->questions->findScoped((int) $bqId, $schoolId);
            if (! $bq || $bq->status !== BankQuestion::STATUS_APPROVED) {
                continue;
            }
            if (in_array($bq->id, $existing, true)) {
                continue;
            }

            QbExamQuestion::create([
                'qb_exam_id' => $exam->id,
                'bank_question_id' => $bq->id,
                'question_type' => $bq->type,
                'body' => $bq->body_ar ?? $bq->body_en,
                'attachment_path' => $bq->attachment_path,
                'answer_snapshot' => $bq->answer_data, // frozen answers
                'question_snapshot' => $bq->only([
                    'id', 'question_code', 'type', 'body_ar', 'body_en', 'difficulty',
                    'points', 'question_category', 'subject_id', 'skill_id', 'explanation',
                ]),
                'marks' => (float) ($bq->points ?? 1),
                'sort_order' => $nextOrder++,
            ]);
            $added++;
        }

        ActivityLog::log('exams.edit', "إضافة {$added} سؤال من البنك إلى اختبار #{$exam->id}", $exam);

        return redirect()->route('admin.qb.exams.show', $exam->id)
            ->with('success', "تمت إضافة {$added} سؤال من بنك الأسئلة (نسخة Snapshot).");
    }

    public function removeQuestion(int $examId, int $examQuestionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('exams.edit'), 403);

        $exam = $this->loadScopedExam($examId);
        $eq = QbExamQuestion::where('qb_exam_id', $exam->id)->whereKey($examQuestionId)->first();
        abort_if(! $eq, 404);
        $eq->delete();

        return back()->with('success', 'تم حذف السؤال من الاختبار.');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * @param  array<int,string>  $with
     */
    private function loadScopedExam(int $examId, array $with = []): QbExam
    {
        $schoolId = $this->scopedSchoolId();
        $exam = QbExam::query()
            ->with($with)
            ->whereKey($examId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->first();
        abort_if(! $exam, 404);

        return $exam;
    }
}
