<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankQuestion;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use App\Modules\QuestionBankCore\Controllers\Concerns\ResolvesAnswerImages;
use App\Modules\QuestionBankCore\Http\Requests\StoreQuestionRequest;
use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\QuestionBankCore\Repositories\Contracts\PassageRepository;
use App\Modules\QuestionBankCore\Repositories\Contracts\QuestionRepository;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Reading passages / أسئلة القطعة (#252). A passage (قطعة) is one text body that
 * owns several child questions. The passage screens live alongside the existing
 * admin/qb question screens; child questions REUSE the normal question creation
 * (StoreQuestionRequest + CreateQuestion) and are linked through the authoritative
 * `passage_questions` pivot, with passage_id + question_category='passage' set on
 * the child in the same transaction so the link can't drift.
 *
 * Reads gated by canDo('question_banks.view'); writes by create/edit/delete.
 * School scope is fail-closed via scopedSchoolId() (enforced through the bank).
 */
class PassageController extends Controller
{
    use HasSchoolScope;
    use ResolvesAnswerImages;

    public function __construct(
        private PassageRepository $passages,
        private QuestionRepository $questions,
        private QbScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $filters = [
            'q'          => trim((string) $request->get('q', '')),
            'code'       => trim((string) $request->get('code', '')),
            'bank_id'    => $request->get('bank_id'),
            'subject_id' => $request->get('subject_id'),
            'status'     => $request->get('status'),
        ];

        return view('admin.qb.passages.index', [
            'passages' => $this->passages->paginate($schoolId, $filters),
            'filters'  => $filters,
            'banks'    => $this->questions->banksForScope($schoolId),
            'subjects' => $this->scope->subjectsForSchool($schoolId),
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $passage = new Passage(['status' => 'approved', 'difficulty_level' => 1]);

        return view('admin.qb.passages.create', $this->formData($schoolId, $passage));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $data = $this->validatePassage($request);

        $bank = $this->questions->findBankScoped((int) $data['question_bank_id'], $schoolId);
        abort_if(! $bank, 404, 'بنك الأسئلة غير موجود أو خارج نطاقك.');

        $imagePath = $request->hasFile('passage_image')
            ? $request->file('passage_image')->store('passages/'.$bank->id, 'public')
            : null;

        $passage = Passage::create([
            'question_bank_id' => $bank->id,
            'passage_code'     => $data['passage_code'] ?? null,
            'passage_text'     => $data['passage_text'],
            'passage_image'    => $imagePath,
            'subject_id'       => $data['subject_id'] ?? null,
            'semester_id'      => $data['semester_id'] ?? null,
            'week_id'          => $data['week_id'] ?? null,
            'skill_id'         => $data['skill_id'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? 1,
            'status'           => $bank->requires_approval ? 'draft' : ($data['status'] ?? 'approved'),
            'created_by'       => auth()->id(),
        ]);

        ActivityLog::log('question_banks.create', "إضافة قطعة قرائية (#{$passage->id})", $passage);

        return redirect()
            ->route('admin.qb.passages.show', $passage->id)
            ->with('success', 'تمت إضافة القطعة. أضف الآن الأسئلة التابعة لها.');
    }

    public function show(int $passageId): View
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $passage = $this->loadScoped($passageId);

        return view('admin.qb.passages.show', [
            'passage' => $passage,
            'types'   => $this->typeLabels(),
        ]);
    }

    public function edit(int $passageId): View
    {
        abort_unless(auth()->user()->canDo('question_banks.edit'), 403);

        $schoolId = $this->scopedSchoolId();
        $passage = $this->loadScoped($passageId);

        return view('admin.qb.passages.edit', $this->formData($schoolId, $passage));
    }

    public function update(Request $request, int $passageId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.edit'), 403);

        $passage = $this->loadScoped($passageId);
        $data = $this->validatePassage($request);

        if ($request->boolean('remove_image') && $passage->passage_image) {
            Storage::disk('public')->delete($passage->passage_image);
            $passage->passage_image = null;
        }
        if ($request->hasFile('passage_image')) {
            if ($passage->passage_image) {
                Storage::disk('public')->delete($passage->passage_image);
            }
            $passage->passage_image = $request->file('passage_image')->store('passages/'.$passage->question_bank_id, 'public');
        }

        $passage->fill([
            'passage_code'     => $data['passage_code'] ?? null,
            'passage_text'     => $data['passage_text'],
            'subject_id'       => $data['subject_id'] ?? null,
            'semester_id'      => $data['semester_id'] ?? null,
            'week_id'          => $data['week_id'] ?? null,
            'skill_id'         => $data['skill_id'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? 1,
            'status'           => $data['status'] ?? $passage->status,
        ])->save();

        ActivityLog::log('question_banks.edit', "تعديل قطعة قرائية (#{$passage->id})", $passage);

        return redirect()
            ->route('admin.qb.passages.show', $passage->id)
            ->with('success', 'تم تحديث القطعة.');
    }

    public function destroy(int $passageId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.delete'), 403);

        $passage = $this->loadScoped($passageId);

        // Detaching is safe (pivot rows only); child questions are archived rather
        // than destroyed so any exam usage is preserved.
        DB::transaction(function () use ($passage) {
            $childIds = $passage->questions()->pluck('bank_questions.id');
            $passage->questions()->detach();
            BankQuestion::whereIn('id', $childIds)->update([
                'passage_id'  => null,
                'status'      => BankQuestion::STATUS_ARCHIVED,
                'archived_at' => now(),
            ]);
            $passage->delete();
        });

        ActivityLog::log('question_banks.delete', "حذف قطعة قرائية (#{$passage->id})", $passage);

        return redirect()
            ->route('admin.qb.passages.index')
            ->with('success', 'تم حذف القطعة وأرشفة أسئلتها التابعة.');
    }

    // ── child questions ─────────────────────────────────────────────────────

    public function createQuestion(Request $request, int $passageId): View
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $passage = $this->loadScoped($passageId);

        $type = $request->get('type', 'mcq');
        if (! in_array($type, BankQuestion::TYPES, true)) {
            $type = 'mcq';
        }

        // Pre-bind the child to the passage's bank/subject/category so the reused
        // question form targets the passage automatically.
        $question = new BankQuestion([
            'type'              => $type,
            'difficulty'        => $passage->difficulty_level ?? 1,
            'points'            => 1,
            'status'            => 'approved',
            'question_category' => 'passage',
            'question_bank_id'  => $passage->question_bank_id,
            'subject_id'        => $passage->subject_id,
            'skill_id'          => $passage->skill_id,
            'semester_id'       => $passage->semester_id,
            'week_id'           => $passage->week_id,
            'passage_id'        => $passage->id,
        ]);

        return view('admin.qb.passages.question_create', array_merge(
            $this->questionFormData($schoolId, $question),
            ['passage' => $passage]
        ));
    }

    public function storeQuestion(StoreQuestionRequest $request, int $passageId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $passage = $this->loadScoped($passageId);

        // Children always belong to the passage's own bank — ignore any posted bank.
        $bank = $this->questions->findBankScoped((int) $passage->question_bank_id, $schoolId);
        abort_if(! $bank, 404, 'بنك القطعة غير موجود أو خارج نطاقك.');

        $data = $request->validated();
        $data['question_category'] = 'passage';
        $data['passage_id'] = $passage->id;
        $data['attachment_path'] = $request->hasFile('attachment')
            ? $request->file('attachment')->store('bank-questions/'.$bank->id, 'public')
            : null;
        $data['question_content_type'] = $data['attachment_path'] ? 'mixed' : 'text';
        $data = $this->resolveAnswerImages($request, $bank, $data);
        // Same approval gate as the normal question path: a non-approver on a
        // requires_approval bank can't self-approve a passage child.
        $default = $bank->requires_approval ? BankQuestion::STATUS_PENDING_REVIEW : BankQuestion::STATUS_APPROVED;
        $requested = $data['status'] ?? $default;
        if ($requested === BankQuestion::STATUS_APPROVED
            && ! (auth()->user()->canDo('question_banks.approve') || ! $bank->requires_approval)) {
            $requested = BankQuestion::STATUS_PENDING_REVIEW;
        }
        $data['status'] = $requested;

        DB::transaction(function () use ($bank, $data, $passage) {
            $question = app(CreateQuestion::class)->execute($bank, $data);
            // Authoritative link is the pivot; passage_id was set above for fast reads.
            $sort = (int) $passage->questions()->max('passage_questions.sort_order') + 1;
            $passage->questions()->attach($question->id, ['sort_order' => $sort]);
        });

        return redirect()
            ->route('admin.qb.passages.show', $passage->id)
            ->with('success', 'تمت إضافة السؤال إلى القطعة.');
    }

    public function detachQuestion(int $passageId, int $questionId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.delete'), 403);

        $passage = $this->loadScoped($passageId);

        DB::transaction(function () use ($passage, $questionId) {
            // Only act if the question is actually attached to THIS passage —
            // prevents archiving an arbitrary/out-of-scope question by id (IDOR).
            $detached = $passage->questions()->detach($questionId);
            abort_if($detached === 0, 404);

            BankQuestion::where('id', $questionId)
                ->where('passage_id', $passage->id)
                ->update([
                    'passage_id'  => null,
                    'status'      => BankQuestion::STATUS_ARCHIVED,
                    'archived_at' => now(),
                ]);
        });

        ActivityLog::log('question_banks.delete', "إزالة سؤال (#{$questionId}) من القطعة (#{$passage->id})", $passage);

        return redirect()
            ->route('admin.qb.passages.show', $passage->id)
            ->with('success', 'تم حذف السؤال الفرعي وأرشفته.');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function loadScoped(int $passageId): Passage
    {
        $passage = $this->passages->findScoped($passageId, $this->scopedSchoolId());
        abort_if(! $passage, 404);

        return $passage;
    }

    /**
     * @return array<string,mixed>
     */
    private function validatePassage(Request $request): array
    {
        return $request->validate([
            'question_bank_id' => ['required', 'integer', 'exists:question_banks,id'],
            'passage_code'     => ['nullable', 'string', 'max:60'],
            'passage_text'     => ['required', 'string'],
            'passage_image'    => ['nullable', 'image', 'max:10240'],
            'remove_image'     => ['nullable', 'boolean'],
            'subject_id'       => ['nullable', 'integer', 'exists:subjects,id'],
            'semester_id'      => ['nullable', 'integer', 'exists:academic_terms,id'],
            'week_id'          => ['nullable', 'integer', 'exists:study_weeks,id'],
            'skill_id'         => ['nullable', 'integer', 'exists:skills,id'],
            'difficulty_level' => ['nullable', 'integer', 'min:1', 'max:3'],
            'status'           => ['nullable', 'in:draft,pending_review,approved,rejected,archived'],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function formData(?int $schoolId, Passage $passage): array
    {
        return [
            'passage'      => $passage,
            'banks'        => $this->questions->banksForScope($schoolId),
            'subjects'     => $this->scope->subjectsForSchool($schoolId),
            'skills'       => $this->scope->skillsForSchool($schoolId),
            'semesters'    => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
            'difficulties' => $this->difficultyLabels(),
            'statuses'     => $this->statusLabels(),
        ];
    }

    /**
     * Form data for the reused question _form when adding a child to a passage.
     *
     * @return array<string,mixed>
     */
    private function questionFormData(?int $schoolId, BankQuestion $question): array
    {
        return [
            'question'     => $question,
            'banks'        => $this->questions->banksForScope($schoolId),
            'tree'         => $this->scope->schoolTree($schoolId),
            'subjects'     => $this->scope->subjectsForSchool($schoolId),
            'semesters'    => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
            'skills'       => $this->scope->skillsForSchool($schoolId),
            'standards'    => \App\Modules\QuestionBankCore\Models\Standard::where('status', 'active')->orderBy('name')->get(['id', 'name', 'subject_id']),
            'types'        => $this->typeLabels(),
            'statuses'     => $this->statusLabels(),
            'difficulties' => $this->difficultyLabels(),
            'scopeService' => $this->scope,
            'schoolId'     => $schoolId,
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

    private function difficultyLabels(): array
    {
        return [1 => 'سهل', 2 => 'متوسط', 3 => 'صعب'];
    }
}
