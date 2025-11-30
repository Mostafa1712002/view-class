<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;

class ExamQuestionController extends Controller
{
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

    /**
     * Update exam total marks based on questions.
     */
    private function updateExamTotalMarks(Exam $exam): void
    {
        $totalMarks = $exam->questions()->sum('marks');
        $exam->update(['total_marks' => $totalMarks]);
    }
}
