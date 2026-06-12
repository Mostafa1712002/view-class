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
        $data = $this->validateQuestion($request);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('bank-questions/'.$bank->id, 'public');
        }

        $bank->questions()->create([
            'lesson_id' => $data['lesson_id'] ?? null,
            'type' => $data['type'],
            'body_ar' => $data['body_ar'],
            'body_en' => $data['body_en'] ?? null,
            'answer_data' => $this->extractAnswerData($data, $request),
            'difficulty' => $data['difficulty'] ?? 1,
            'points' => $data['points'] ?? 1,
            'attachment_path' => $attachmentPath,
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
        $bank = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();
        $data = $this->validateQuestion($request);

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

        $question->update([
            'lesson_id' => $data['lesson_id'] ?? null,
            'type' => $data['type'],
            'body_ar' => $data['body_ar'],
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
        $bank = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();
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

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:'.implode(',', BankQuestion::TYPES)],
            'body_ar' => ['required', 'string'],
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
        ]);
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
