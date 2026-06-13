<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

class ExamQuestionController extends Controller
{
    use HasSchoolScope;
    /**
     * Display questions for an exam.
     */
    public function index(Exam $exam)
    {
        $questions = $exam->questions()->orderBy('order')->get();
        $totalMarks = $questions->sum('marks');

        return view('admin.exams.questions.index', compact('exam', 'questions', 'totalMarks'));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Exam $exam)
    {
        $nextOrder = $exam->questions()->max('order') + 1;

        return view('admin.exams.questions.create', compact('exam', 'nextOrder'));
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,short_answer,essay',
            'marks' => 'required|numeric|min:0.5',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'correct_answer' => 'nullable|string',
            'explanation' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        // Filter empty options
        if (isset($validated['options'])) {
            $validated['options'] = array_filter($validated['options'], fn($opt) => !empty(trim($opt)));
            $validated['options'] = array_values($validated['options']);
        }

        $exam->questions()->create($validated);

        // Update exam total marks
        $this->updateExamTotalMarks($exam);

        if ($request->has('add_another')) {
            return redirect()->route('admin.exams.questions.create', $exam)
                ->with('success', 'تم إضافة السؤال بنجاح. أضف سؤالاً آخر.');
        }

        return redirect()->route('admin.exams.questions.index', $exam)
            ->with('success', 'تم إضافة السؤال بنجاح.');
    }

    /**
     * Show the form for editing a question.
     */
    public function edit(Exam $exam, ExamQuestion $question)
    {
        return view('admin.exams.questions.edit', compact('exam', 'question'));
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, Exam $exam, ExamQuestion $question)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,short_answer,essay',
            'marks' => 'required|numeric|min:0.5',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'correct_answer' => 'nullable|string',
            'explanation' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        // Filter empty options
        if (isset($validated['options'])) {
            $validated['options'] = array_filter($validated['options'], fn($opt) => !empty(trim($opt)));
            $validated['options'] = array_values($validated['options']);
        }

        $question->update($validated);

        // Update exam total marks
        $this->updateExamTotalMarks($exam);

        return redirect()->route('admin.exams.questions.index', $exam)
            ->with('success', 'تم تحديث السؤال بنجاح.');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Exam $exam, ExamQuestion $question)
    {
        $question->delete();

        // Reorder remaining questions
        $exam->questions()->orderBy('order')->get()->each(function ($q, $index) {
            $q->update(['order' => $index + 1]);
        });

        // Update exam total marks
        $this->updateExamTotalMarks($exam);

        return redirect()->route('admin.exams.questions.index', $exam)
            ->with('success', 'تم حذف السؤال بنجاح.');
    }

    /**
     * Reorder questions.
     */
    public function reorder(Request $request, Exam $exam)
    {
        $request->validate([
            'questions' => 'required|array',
            'questions.*' => 'exists:exam_questions,id',
        ]);

        foreach ($request->questions as $index => $questionId) {
            ExamQuestion::where('id', $questionId)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(Exam $exam, ExamQuestion $question)
    {
        $newQuestion = $question->replicate();
        $newQuestion->order = $exam->questions()->max('order') + 1;
        $newQuestion->save();

        // Update exam total marks
        $this->updateExamTotalMarks($exam);

        return redirect()->route('admin.exams.questions.index', $exam)
            ->with('success', 'تم نسخ السؤال بنجاح.');
    }

    // ─── Bank-picker integration (#217) ──────────────────────────────────────

    /**
     * Show the bank-question picker page for this exam.
     * Lists the school's approved bank questions with checkboxes.
     */
    public function bankPicker(Request $request, Exam $exam)
    {
        $schoolId = $this->resolveExamSchool($exam);

        // Base query: approved questions from this school's banks only.
        $query = BankQuestion::query()
            ->whereHas('bank', fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', BankQuestion::STATUS_APPROVED)
            ->with(['bank:id,name_ar,name_en', 'lesson:id,name_ar']);

        // Optional filters for UX
        if ($bankId = $request->integer('bank_id')) {
            $query->where('question_bank_id', $bankId);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($q = $request->get('q')) {
            $query->where('body_ar', 'like', '%' . $q . '%');
        }

        $bankQuestions = $query->orderByDesc('id')->paginate(30)->withQueryString();

        // Banks dropdown for filter
        $banks = QuestionBank::where('school_id', $schoolId)
            ->where('status', QuestionBank::STATUS_ACTIVE)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return view('admin.exams.questions.bank-picker', compact('exam', 'bankQuestions', 'banks'));
    }

    /**
     * Copy selected bank questions into exam_questions for this exam.
     *
     * Type mapping (bank → exam):
     *   mcq          → multiple_choice
     *   true_false   → true_false
     *   short        → short_answer
     *   essay        → essay
     *   matching     → essay   (no native matching type; copy body as context)
     *   fill_blank   → short_answer (answers stored as semicolon-separated in correct_answer)
     *
     * Only APPROVED questions that belong to the actor's school may be added.
     */
    public function addFromBank(Request $request, Exam $exam)
    {
        $schoolId = $this->resolveExamSchool($exam);

        $request->validate([
            'bank_question_ids'   => ['required', 'array', 'min:1'],
            'bank_question_ids.*' => ['required', 'integer'],
        ]);

        $ids = $request->input('bank_question_ids');

        // Load bank questions scoped to this school + approved status only.
        $bankQuestions = BankQuestion::query()
            ->whereIn('id', $ids)
            ->where('status', BankQuestion::STATUS_APPROVED)
            ->whereHas('bank', fn ($q) => $q->where('school_id', $schoolId))
            ->get();

        if ($bankQuestions->isEmpty()) {
            return redirect()->route('admin.exams.questions.bank-picker', $exam)
                ->with('error', 'لم يتم العثور على أسئلة معتمدة تعود لمدرستك.');
        }

        $nextOrder = $exam->questions()->max('order') + 1;
        $added     = 0;
        $skipped   = [];

        foreach ($bankQuestions as $bq) {
            [$examType, $options, $correctAnswer] = $this->mapBankToExamQuestion($bq);

            if ($examType === null) {
                $skipped[] = $bq->body_ar ?? $bq->question_code ?? "#{$bq->id}";
                continue;
            }

            $exam->questions()->create([
                'question'               => $bq->body_ar ?? $bq->body_en ?? '',
                'type'                   => $examType,
                'options'                => $options,
                'correct_answer'         => $correctAnswer,
                'marks'                  => (float) ($bq->points ?? 1),
                'explanation'            => null,
                'order'                  => $nextOrder++,
                'source_bank_question_id'=> $bq->id,
            ]);
            $added++;
        }

        $this->updateExamTotalMarks($exam);

        $msg = "تمت إضافة {$added} سؤال من بنك الأسئلة.";
        if ($skipped) {
            $msg .= ' تم تخطي ' . count($skipped) . ' سؤال (نوع لا يمكن ربطه تلقائياً).';
        }

        return redirect()->route('admin.exams.questions.index', $exam)->with('success', $msg);
    }

    /**
     * Map a BankQuestion to (examType, options, correctAnswer).
     * Returns [null, null, null] to skip an unmappable question.
     *
     * Mapping:
     *   mcq        → multiple_choice  — options from answer_data.options, correct from answer_data.correct (index)
     *   true_false → true_false       — correct from answer_data.correct ('true'/'false')
     *   short      → short_answer     — correct from answer_data.model_answer
     *   essay      → essay            — correct = model answer (informational)
     *   matching   → essay            — body + pairs serialised into question text (no native matching)
     *   fill_blank → short_answer     — blanks joined with " / " as the correct answer hint
     */
    private function mapBankToExamQuestion(BankQuestion $bq): array
    {
        $data = $bq->answer_data ?? [];

        return match ($bq->type) {
            'mcq' => (function () use ($bq, $data) {
                $opts    = $data['options'] ?? [];
                $correct = null;
                if (isset($data['correct']) && isset($opts[$data['correct']])) {
                    $correct = $opts[$data['correct']];
                }
                return ['multiple_choice', $opts ?: null, $correct];
            })(),

            'true_false' => ['true_false', null, $data['correct'] ?? null],

            'short' => ['short_answer', null, $data['model_answer'] ?? null],

            'essay' => ['essay', null, $data['model_answer'] ?? null],

            'matching' => (function () use ($bq, $data) {
                // Store matching pairs inside the question text as a table note.
                $pairs = $data['pairs'] ?? [];
                $note  = '';
                if ($pairs) {
                    $note = "\n\n[جدول التوصيل]\n";
                    foreach ($pairs as $pair) {
                        $note .= ($pair['left'] ?? '') . ' ⟷ ' . ($pair['right'] ?? '') . "\n";
                    }
                }
                return ['essay', null, $note ?: null];
            })(),

            'fill_blank' => (function () use ($data) {
                $blanks = $data['blanks'] ?? [];
                return ['short_answer', null, implode(' / ', array_filter($blanks)) ?: null];
            })(),

            default => [null, null, null],
        };
    }

    /**
     * Resolve the school to scope bank questions by, enforcing tenant access.
     *
     * - Super-admin (no single active school) operates on the exam's own school.
     * - A scoped admin/teacher must have an active school that matches the exam's.
     * Returns the effective school id, or aborts 403 on a cross-tenant attempt.
     */
    private function resolveExamSchool(Exam $exam): int
    {
        $examSchoolId = $exam->classRoom?->school_id ?? null;
        if (! $examSchoolId) {
            abort(403, 'لا يمكن تحديد مدرسة هذا الاختبار.');
        }

        $schoolId = $this->activeSchoolId();

        // Super-admin: no single active school → act within the exam's school.
        if ($schoolId === null) {
            abort_if(! auth()->user()->isSuperAdmin(), 403, 'لم يتم تحديد المدرسة.');

            return (int) $examSchoolId;
        }

        // Scoped actor: their active school must own the exam.
        abort_if((int) $examSchoolId !== (int) $schoolId, 403, 'لا يمكن الوصول إلى هذا الاختبار.');

        return (int) $schoolId;
    }

    /**
     * Update exam total marks based on questions.
     */
    private function updateExamTotalMarks(Exam $exam): void
    {
        $totalMarks = $exam->questions()->sum('marks');
        $exam->update(['total_marks' => $totalMarks]);
    }
}
