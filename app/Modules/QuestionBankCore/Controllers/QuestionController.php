<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use App\Modules\QuestionBankCore\Actions\UpdateQuestion;
use App\Modules\QuestionBankCore\Controllers\Concerns\ResolvesAnswerImages;
use App\Modules\QuestionBankCore\Http\Requests\StoreQuestionRequest;
use App\Modules\QuestionBankCore\Repositories\Contracts\QuestionRepository;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Rebuilt question list + manual create/edit (#250, #253). Lives alongside the
 * legacy admin/question-banks feature without touching it: new URL prefix
 * (admin/qb), new route names (admin.qb.*), new views (admin/qb).
 *
 * Reads are gated by route middleware (role:super-admin,school-admin,teacher) +
 * canDo('question_banks.view'); every write is gated by canDo and school scope.
 */
class QuestionController extends Controller
{
    use HasSchoolScope;
    use ResolvesAnswerImages;

    public function __construct(
        private QuestionRepository $questions,
        private QbScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $filters = $this->extractFilters($request);
        $questions = $this->questions->paginate($schoolId, $filters);

        return view('admin.qb.questions.index', [
            'questions'   => $questions,
            'filters'     => $filters,
            'banks'       => $this->questions->banksForScope($schoolId),
            'subjects'    => $this->scope->subjectsForSchool($schoolId),
            'skills'      => $this->scope->skillsForSchool($schoolId),
            'types'       => $this->typeLabels(),
            'statuses'    => $this->statusLabels(),
            'categories'  => $this->categoryLabels(),
            'difficulties' => $this->difficultyLabels(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $type = $request->get('type', 'mcq');
        if (! in_array($type, BankQuestion::TYPES, true)) {
            $type = 'mcq';
        }

        $category = $request->get('category', 'normal');
        if (! in_array($category, ['normal', 'tahsili'], true)) {
            $category = 'normal';
        }

        $question = new BankQuestion([
            'type' => $type, 'difficulty' => 1, 'points' => 1,
            'status' => 'approved', 'question_category' => $category,
        ]);

        return view('admin.qb.questions.create', $this->formData($schoolId, $question));
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $bank = $this->questions->findBankScoped((int) $request->input('question_bank_id'), $schoolId);
        abort_if(! $bank, 404, 'بنك الأسئلة غير موجود أو خارج نطاقك.');

        $data = $request->validated();
        $data = $this->resolveAttachment($request, $bank, $data, null);
        $data = $this->resolveAnswerImages($request, $bank, $data);
        $data['status'] = $this->resolveCreateStatus($bank, $data['status'] ?? null);

        app(CreateQuestion::class)->execute($bank, $data);

        return redirect()
            ->route('admin.qb.questions.index')
            ->with('success', 'تمت إضافة السؤال بنجاح.');
    }

    public function edit(Request $request, int $questionId): View
    {
        abort_unless(auth()->user()->canDo('question_banks.edit'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        return view('admin.qb.questions.edit', $this->formData($schoolId, $question));
    }

    public function update(StoreQuestionRequest $request, int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.edit'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        // Guard: an approved question may only be edited by super/school admin.
        if ($question->status === BankQuestion::STATUS_APPROVED) {
            $user = auth()->user();
            if (! ($user->isSuperAdmin() || $user->isSchoolAdmin())) {
                return back()->with('error', __('exam_bank.guard_approved_edit_blocked'));
            }
        }

        $data = $request->validated();
        $data = $this->resolveAttachment($request, $question->bank, $data, $question);
        $data = $this->resolveAnswerImages($request, $question->bank, $data);
        $data['status'] = $this->resolveUpdateStatus($question, $data['status'] ?? null);

        app(UpdateQuestion::class)->execute($question, $data);

        return redirect()
            ->route('admin.qb.questions.index')
            ->with('success', 'تم تحديث السؤال بنجاح.');
    }

    public function destroy(int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.delete'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        // Used in an exam/assignment → archive instead of hard delete.
        if ($this->questions->isUsedInExam($question->id)) {
            $question->update(['status' => BankQuestion::STATUS_ARCHIVED, 'archived_at' => now()]);

            return redirect()
                ->route('admin.qb.questions.index')
                ->with('error', __('exam_bank.guard_used_delete_archived'));
        }

        ActivityLog::log('question_banks.delete', "حذف سؤال (#{$question->id})", $question, $question->only(['type', 'question_code']), null);
        $question->delete();

        return redirect()
            ->route('admin.qb.questions.index')
            ->with('success', 'تم حذف السؤال.');
    }

    public function archive(int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.archive'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        $question->update(['status' => BankQuestion::STATUS_ARCHIVED, 'archived_at' => now()]);
        ActivityLog::log('question_banks.archive', "أرشفة سؤال (#{$question->id})", $question);

        return redirect()
            ->route('admin.qb.questions.index')
            ->with('success', 'تمت أرشفة السؤال.');
    }

    public function duplicate(int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        $copy = $question->replicate(['created_at', 'updated_at', 'archived_at']);
        $copy->status = BankQuestion::STATUS_DRAFT;
        $copy->question_code = null; // codes are unique per bank
        $copy->body_ar = ($question->body_ar ?? '') . ' — نسخة';
        $copy->created_by = auth()->id();
        $copy->save();

        // Copy normalized answers too.
        foreach ($question->answers as $ans) {
            $copy->answers()->create($ans->only([
                'answer_text', 'answer_image', 'answer_content_type', 'is_correct',
                'sort_order', 'blank_number', 'column_a_text', 'column_a_image',
                'column_b_text', 'column_b_image',
            ]));
        }

        ActivityLog::log('question_banks.create', "نسخ سؤال (#{$question->id} → #{$copy->id})", $copy);

        return redirect()
            ->route('admin.qb.questions.edit', $copy->id)
            ->with('success', 'تم نسخ السؤال.');
    }

    /**
     * #256 — submit a question for review (any status → pending_review). Gated by
     * .edit (an author may send their own draft for review). No approve needed.
     */
    public function submit(int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.edit'), 403);

        $question = $this->loadScopedQuestion($questionId);
        $question->update([
            'status'          => BankQuestion::STATUS_PENDING_REVIEW,
            'rejected_reason' => null,
            'reviewed_by'     => null,
            'reviewed_at'     => null,
        ]);
        ActivityLog::log('question_banks.edit', "إرسال سؤال للمراجعة (#{$question->id})", $question);

        return redirect()->route('admin.qb.questions.index')->with('success', 'تم إرسال السؤال للمراجعة.');
    }

    /**
     * #256 — approve a question (→ approved). Gated by .approve (fails closed).
     */
    public function approve(int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.approve'), 403);

        $question = $this->loadScopedQuestion($questionId);
        $question->update([
            'status'          => BankQuestion::STATUS_APPROVED,
            'rejected_reason' => null,
            'reviewed_by'     => auth()->id(),
            'reviewed_at'     => now(),
        ]);
        ActivityLog::log('question_banks.approve', "اعتماد سؤال (#{$question->id})", $question);

        return redirect()->route('admin.qb.questions.index')->with('success', 'تم اعتماد السؤال.');
    }

    /**
     * #256 — reject a question (→ rejected, with a stored reason). Gated by .reject.
     */
    public function reject(Request $request, int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.reject'), 403);

        $reason = trim((string) $request->input('rejected_reason', ''));
        if ($reason === '') {
            return back()->with('error', 'يجب إدخال سبب الرفض.');
        }

        $question = $this->loadScopedQuestion($questionId);
        $question->update([
            'status'          => BankQuestion::STATUS_REJECTED,
            'rejected_reason' => $reason,
            'reviewed_by'     => auth()->id(),
            'reviewed_at'     => now(),
        ]);
        ActivityLog::log('question_banks.reject', "رفض سؤال (#{$question->id})", $question, null, ['reason' => $reason]);

        return redirect()->route('admin.qb.questions.index')->with('success', 'تم رفض السؤال.');
    }

    /**
     * Answer fragment (الإجابة الصحيحة) — gated; only privileged users see it.
     */
    public function answer(int $questionId): View
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);
        $question->load('answers');

        return view('admin.qb.partials.answer-modal', compact('question'));
    }

    /**
     * Classes/scope fragment (عرض الصفوف) for a question.
     */
    public function classes(int $questionId): View
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        $school = $question->bank->school_id
            ? \App\Models\School::find($question->bank->school_id)
            : null;
        $class = $question->class_id ? \App\Models\ClassRoom::find($question->class_id) : null;
        $semester = $question->semester_id ? \App\Models\AcademicTerm::find($question->semester_id) : null;

        return view('admin.qb.partials.classes-modal', compact('question', 'school', 'class', 'semester'));
    }

    // ── helpers ───────────────────────────────────────────────────────────

    /**
     * Load a question within the caller's school scope or 404.
     */
    private function loadScopedQuestion(int $questionId): BankQuestion
    {
        $schoolId = $this->scopedSchoolId();
        $question = $this->questions->findScoped($questionId, $schoolId);
        abort_if(! $question, 404);

        return $question;
    }

    /**
     * #256 — status a NEW question may take, clamped by permission. On a bank that
     * requires approval, the safe default is pending_review (so a non-approver's
     * question waits for review, per the card). On an auto-approve bank, approved
     * is the normal birth state.
     */
    private function resolveCreateStatus(QuestionBank $bank, ?string $requested): string
    {
        $default = $bank->requires_approval ? BankQuestion::STATUS_PENDING_REVIEW : BankQuestion::STATUS_APPROVED;

        return $this->clampStatus($bank, $requested ?? $default, $default);
    }

    /**
     * #256 — status an EDIT may set, clamped by permission. A no-op edit keeps the
     * current status (so editing an approved question on a requires_approval bank
     * doesn't silently demote it). A move to a gated state needs the privilege.
     */
    private function resolveUpdateStatus(BankQuestion $question, ?string $requested): string
    {
        $requested ??= $question->status;
        if ($requested === $question->status) {
            return $question->status;
        }

        return $this->clampStatus($question->bank, $requested, $question->status);
    }

    /**
     * Coerce a requested status to one the current user is allowed to set.
     *   - `approved`  needs .approve, OR the bank not requiring approval (auto-approve).
     *   - `rejected`  needs .reject.
     *   - draft / pending_review / archived pass through.
     * The fallback returned on a denied `approved` is never itself `approved`.
     */
    private function clampStatus(QuestionBank $bank, string $requested, string $fallback): string
    {
        if (! in_array($requested, BankQuestion::STATUSES, true)) {
            return $fallback;
        }

        $user = auth()->user();
        if ($requested === BankQuestion::STATUS_APPROVED
            && ! ($user->canDo('question_banks.approve') || ! $bank->requires_approval)) {
            return BankQuestion::STATUS_PENDING_REVIEW;
        }
        if ($requested === BankQuestion::STATUS_REJECTED && ! $user->canDo('question_banks.reject')) {
            return $fallback;
        }

        return $requested;
    }

    private function formData(?int $schoolId, BankQuestion $question): array
    {
        return [
            'question'   => $question,
            'banks'      => $this->questions->banksForScope($schoolId),
            'tree'       => $this->scope->schoolTree($schoolId),
            'subjects'   => $this->scope->subjectsForSchool($schoolId),
            'semesters'  => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
            'skills'     => $this->scope->skillsForSchool($schoolId),
            'standards'  => \App\Modules\QuestionBankCore\Models\Standard::where('status', 'active')->orderBy('name')->get(['id', 'name', 'subject_id']),
            'types'      => $this->typeLabels(),
            'statuses'   => $this->statusLabels(),
            'difficulties' => $this->difficultyLabels(),
            'scopeService' => $this->scope,
            'schoolId'   => $schoolId,
        ];
    }

    /**
     * Resolve the stored attachment_path + content_type from the upload.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function resolveAttachment(Request $request, QuestionBank $bank, array $data, ?BankQuestion $existing): array
    {
        $attachmentPath = $existing?->attachment_path;

        if ($request->boolean('remove_attachment') && $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
            $attachmentPath = null;
        }
        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = $request->file('attachment')->store('bank-questions/'.$bank->id, 'public');
        }

        $isFullImage = $request->boolean('is_full_image_question');
        if ($isFullImage && ! $attachmentPath) {
            throw ValidationException::withMessages(['attachment' => __('questions.errors.image_required')]);
        }

        $data['attachment_path'] = $attachmentPath;
        $data['question_content_type'] = $this->resolveContentType($data, $attachmentPath, $isFullImage);

        return $data;
    }

    private function resolveContentType(array $data, ?string $attachmentPath, bool $isFullImage): string
    {
        $explicit = $data['question_content_type'] ?? null;
        if (in_array($explicit, ['text', 'image', 'mixed'], true)) {
            return $explicit;
        }
        if ($isFullImage) {
            return 'image';
        }
        return $attachmentPath ? 'mixed' : 'text';
    }

    private function extractFilters(Request $request): array
    {
        return [
            'q'           => trim((string) $request->get('q', '')),
            'code'        => trim((string) $request->get('code', '')),
            'bank_id'     => $request->get('bank_id'),
            'subject_id'  => $request->get('subject_id'),
            'grade_id'    => $request->get('grade_id'),
            'class_id'    => $request->get('class_id'),
            'semester_id' => $request->get('semester_id'),
            'week_id'     => $request->get('week_id'),
            'skill_id'    => $request->get('skill_id'),
            'type'        => $request->get('type'),
            'category'    => $request->get('category'),
            'difficulty'  => $request->get('difficulty'),
            'status'      => $request->get('status'),
            'source'      => $request->get('source'),
            'has_image'   => $request->boolean('has_image'),
            'full_image_only' => $request->boolean('full_image_only'),
            'date_from'   => $request->get('date_from'),
            'date_to'     => $request->get('date_to'),
        ];
    }

    private function typeLabels(): array
    {
        return [
            'mcq'        => 'اختيار من متعدد',
            'true_false' => 'صح وخطأ',
            'essay'      => 'سؤال إنشائي',
            'short'      => 'إجابة قصيرة',
            'fill_blank' => 'املأ الفراغ',
            'matching'   => 'توصيل',
        ];
    }

    private function statusLabels(): array
    {
        return [
            'draft'          => 'مسودة',
            'pending_review' => 'بانتظار المراجعة',
            'approved'       => 'معتمد',
            'rejected'       => 'مرفوض',
            'archived'       => 'مؤرشف',
        ];
    }

    private function categoryLabels(): array
    {
        return ['normal' => 'عادي', 'tahsili' => 'تحصيلي', 'passage' => 'قطعة'];
    }

    private function difficultyLabels(): array
    {
        return [1 => 'سهل', 2 => 'متوسط', 3 => 'صعب'];
    }
}
