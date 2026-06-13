<?php

namespace App\Modules\QuestionBanks\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Models\SubjectLesson;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BankQuestionController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QuestionBankRepository $banks) {}

    /**
     * Index of questions in a bank with filters.
     */
    public function index(Request $request, int $bankId): View
    {
        $bank = $this->resolveBank($bankId);

        $query = $bank->questions()
            ->with(['lesson', 'creator'])
            ->latest();

        // Legacy text search (body_ar / body_en)
        if ($q = $request->get('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('body_ar', 'like', "%{$q}%")
                  ->orWhere('body_en', 'like', "%{$q}%");
            });
        }

        // Question code search
        if ($code = $request->get('code')) {
            $query->where('question_code', 'like', "%{$code}%");
        }

        // Type filter
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Content type filter (text / image / mixed)
        if ($contentType = $request->get('content_type')) {
            $query->where('question_content_type', $contentType);
        }

        // Difficulty filter
        if (($difficulty = $request->get('difficulty')) !== null && $difficulty !== '') {
            $query->where('difficulty', (int) $difficulty);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Source filter
        if ($source = $request->get('source')) {
            $query->where('source', 'like', "%{$source}%");
        }

        // Lesson filter
        if ($lessonId = $request->get('lesson_id')) {
            $query->where('lesson_id', $lessonId);
        }

        // Image flags
        if ($request->boolean('has_image')) {
            $query->whereNotNull('attachment_path');
        }
        if ($request->boolean('full_image_only')) {
            $query->where('is_full_image_question', true);
        }

        $questions = $query->paginate(25)->withQueryString();
        $lessons = $this->lessonsForBank($bank);

        // Per-type counts for the bank header
        $typeCounts = $bank->questions()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('admin.question-banks.questions.index', compact('bank', 'questions', 'lessons', 'typeCounts'));
    }

    public function create(Request $request, int $bankId): View
    {
        $bank = $this->resolveBank($bankId);
        $type = $request->get('type', 'mcq');
        if (! in_array($type, BankQuestion::TYPES, true)) {
            $type = 'mcq';
        }

        $question = new BankQuestion([
            'type' => $type,
            'difficulty' => 1,
            'points' => 1,
            'status' => 'approved',
        ]);

        $lessons = $this->lessonsForBank($bank);

        return view('admin.question-banks.questions.create', compact('bank', 'question', 'lessons'));
    }

    public function store(Request $request, int $bankId): RedirectResponse
    {
        $bank = $this->resolveBank($bankId);
        $data = $this->validateQuestion($request, $bank);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('bank-questions/'.$bank->id, 'public');
        }

        $isFullImage = $request->boolean('is_full_image_question');
        // A full-image question must actually carry an image.
        if ($isFullImage && ! $attachmentPath) {
            throw ValidationException::withMessages(['attachment' => __('questions.errors.image_required')]);
        }

        $bank->questions()->create([
            'lesson_id' => $data['lesson_id'] ?? null,
            'type' => $data['type'],
            'question_code' => $data['question_code'] ?? null,
            'question_content_type' => $this->resolveContentType($data, $attachmentPath, $isFullImage),
            'is_full_image_question' => $isFullImage,
            'body_ar' => $data['body_ar'] ?? null,
            'body_en' => $data['body_en'] ?? null,
            'answer_data' => $this->extractAnswerData($data, $request),
            'difficulty' => $data['difficulty'] ?? 1,
            'points' => $data['points'] ?? 1,
            'attachment_path' => $attachmentPath,
            'source' => 'manual',
            'status' => $data['status'] ?? 'approved',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.question-banks.questions.index', $bank->id)
            ->with('success', __('questions.flash.created'));
    }

    public function edit(int $bankId, int $questionId): View
    {
        $bank = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();
        $lessons = $this->lessonsForBank($bank);

        return view('admin.question-banks.questions.edit', compact('bank', 'question', 'lessons'));
    }

    public function update(Request $request, int $bankId, int $questionId): RedirectResponse
    {
        $bank     = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();

        // ── Guard 3a: block non-privileged users from editing an approved question ──
        // School-admin and super-admin may edit approved questions; teachers cannot.
        if ($question->status === BankQuestion::STATUS_APPROVED) {
            $user = auth()->user();
            if (! ($user->isSuperAdmin() || $user->isSchoolAdmin())) {
                return redirect()
                    ->route('admin.question-banks.questions.index', $bank->id)
                    ->with('error', __('exam_bank.guard_approved_edit_blocked'));
            }
        }

        // ── Guard 3b: block editing a question used in a published/active exam ──
        // "Published" = exam.status in [active, scheduled, completed] OR is_published=true.
        // Only super-admin may force-edit in that case; everyone else must duplicate.
        $usedInPublishedExam = \App\Models\ExamQuestion::query()
            ->where('source_bank_question_id', $question->id)
            ->whereHas('exam', function ($q) {
                $q->where(function ($or) {
                    $or->whereIn('status', ['active', 'scheduled', 'completed'])
                       ->orWhere('is_published', true);
                });
            })
            ->exists();

        if ($usedInPublishedExam && ! auth()->user()->isSuperAdmin()) {
            return redirect()
                ->route('admin.question-banks.questions.index', $bank->id)
                ->with('error', __('exam_bank.guard_used_published_edit_blocked'));
        }

        $data = $this->validateQuestion($request, $bank, $question->id);

        $attachmentPath = $question->attachment_path;
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

        $question->update([
            'lesson_id' => $data['lesson_id'] ?? null,
            'type' => $data['type'],
            'question_code' => $data['question_code'] ?? null,
            'question_content_type' => $this->resolveContentType($data, $attachmentPath, $isFullImage),
            'is_full_image_question' => $isFullImage,
            'body_ar' => $data['body_ar'] ?? null,
            'body_en' => $data['body_en'] ?? null,
            'answer_data' => $this->extractAnswerData($data, $request),
            'difficulty' => $data['difficulty'] ?? 1,
            'points' => $data['points'] ?? 1,
            'attachment_path' => $attachmentPath,
            'status' => $data['status'] ?? 'approved',
        ]);

        return redirect()
            ->route('admin.question-banks.questions.index', $bank->id)
            ->with('success', __('questions.flash.updated'));
    }

    public function destroy(int $bankId, int $questionId): RedirectResponse
    {
        $bank     = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();

        // ── Guard 1: archive instead of delete when the question is used in any exam ──
        $isUsed = \App\Models\ExamQuestion::query()
            ->where('source_bank_question_id', $question->id)
            ->exists();

        if ($isUsed) {
            $question->update(['status' => BankQuestion::STATUS_ARCHIVED]);

            return redirect()
                ->route('admin.question-banks.questions.index', $bank->id)
                ->with('error', __('exam_bank.guard_used_delete_archived'));
        }

        $question->delete();

        return redirect()
            ->route('admin.question-banks.questions.index', $bank->id)
            ->with('success', __('questions.flash.deleted'));
    }

    /**
     * Duplicate a question inside the same bank.
     */
    public function duplicate(int $bankId, int $questionId): RedirectResponse
    {
        $bank = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();

        $copy = $question->replicate(['created_at', 'updated_at']);
        $copy->status = 'draft';
        $copy->body_ar = $question->body_ar . ' — ' . __('questions.copy_suffix');
        $copy->created_by = auth()->id();
        $copy->save();

        return redirect()
            ->route('admin.question-banks.questions.edit', [$bank->id, $copy->id])
            ->with('success', __('questions.flash.duplicated'));
    }

    /**
     * Preview a question as the student would see it. Returns an HTML fragment
     * intended to be loaded into the modal on the index page.
     */
    public function preview(int $bankId, int $questionId): View
    {
        $bank = $this->resolveBank($bankId);
        $question = $bank->questions()->with(['lesson', 'creator'])->whereKey($questionId)->firstOrFail();

        return view('admin.question-banks.questions._preview', compact('bank', 'question'));
    }

    private function resolveBank(int $bankId): QuestionBank
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($bankId, $schoolId);
        abort_if(! $bank, 404);
        return $bank;
    }

    private function validateQuestion(Request $request, QuestionBank $bank, ?int $ignoreId = null): array
    {
        // A full-image question carries no text head, so the question code is the
        // only way to find it — make it mandatory there and optional otherwise.
        $isFullImage = $request->boolean('is_full_image_question');

        return $request->validate([
            'type' => ['required', 'in:'.implode(',', BankQuestion::TYPES)],
            'question_code' => [
                $isFullImage ? 'required' : 'nullable',
                'string', 'max:60',
                // Codes must be unique within the same bank (#213).
                Rule::unique('bank_questions', 'question_code')
                    ->where(fn ($q) => $q->where('question_bank_id', $bank->id)->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],
            'question_content_type' => ['nullable', 'in:text,image,mixed'],
            'is_full_image_question' => ['nullable', 'boolean'],
            // Text head is required unless the whole question is an image.
            'body_ar' => [$isFullImage ? 'nullable' : 'required', 'string'],
            'body_en' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:3'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'status' => ['nullable', 'in:draft,pending_review,approved,rejected,archived'],
            'lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],
            'options_ar' => ['nullable', 'array'],
            'options_ar.*' => ['nullable', 'string'],
            'correct' => ['nullable'],
            'correct_index' => ['nullable', 'integer'],
            'essay_answer' => ['nullable', 'string'],
            'matching_left' => ['nullable', 'array'],
            'matching_left.*' => ['nullable', 'string'],
            'matching_right' => ['nullable', 'array'],
            'matching_right.*' => ['nullable', 'string'],
            'blanks' => ['nullable', 'array'],
            'blanks.*' => ['nullable', 'string'],
            'short_answer' => ['nullable', 'string'],
        ], [
            'question_code.required' => __('questions.errors.code_required_image'),
            'question_code.unique'   => __('questions.errors.code_duplicate'),
        ]);
    }

    /**
     * Resolve the stored content type. An explicit choice wins; otherwise we
     * infer it: full-image → image, text + an attachment → mixed, else text.
     */
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

    private function extractAnswerData(array $data, Request $request): ?array
    {
        switch ($data['type']) {
            case 'mcq':
                $options = array_values(array_filter(
                    $data['options_ar'] ?? [],
                    static fn ($v) => $v !== null && $v !== ''
                ));
                $correct = $data['correct_index'] ?? $data['correct'] ?? null;
                return [
                    'options' => $options,
                    'correct' => is_numeric($correct) ? (int) $correct : null,
                ];

            case 'true_false':
                return ['correct' => $data['correct'] ?? null];

            case 'essay':
                return ['model_answer' => $data['essay_answer'] ?? null];

            case 'short':
                return ['model_answer' => $data['short_answer'] ?? null];

            case 'matching':
                $left = $data['matching_left'] ?? [];
                $right = $data['matching_right'] ?? [];
                $pairs = [];
                $count = max(count($left), count($right));
                for ($i = 0; $i < $count; $i++) {
                    $l = trim((string) ($left[$i] ?? ''));
                    $r = trim((string) ($right[$i] ?? ''));
                    if ($l !== '' && $r !== '') {
                        $pairs[] = ['left' => $l, 'right' => $r];
                    }
                }
                return ['pairs' => $pairs];

            case 'fill_blank':
                $blanks = array_values(array_filter(
                    $data['blanks'] ?? [],
                    static fn ($v) => $v !== null && $v !== ''
                ));
                return ['blanks' => $blanks];
        }
        return null;
    }

    /**
     * Lessons available for the lesson-link picker. Limited to subjects
     * attached to this bank so the dropdown is meaningful.
     */
    private function lessonsForBank(QuestionBank $bank): \Illuminate\Support\Collection
    {
        $subjectIds = $bank->subjects()->pluck('subjects.id');
        if ($subjectIds->isEmpty()) {
            return collect();
        }

        return SubjectLesson::query()
            ->whereHas('unit', function ($q) use ($subjectIds) {
                $q->whereIn('subject_id', $subjectIds);
            })
            ->with('unit:id,subject_id,name_ar')
            ->select('id', 'unit_id', 'name_ar', 'name_en')
            ->orderBy('unit_id')
            ->orderBy('sort_order')
            ->get();
    }
}
