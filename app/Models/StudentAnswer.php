<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    protected $fillable = [
        'student_exam_id',
        'question_id',
        'answer',
        'marks_obtained',
        'is_correct',
        'feedback',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'is_correct' => 'boolean',
    ];

    public function studentExam(): BelongsTo
    {
        return $this->belongsTo(StudentExam::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }

    public function autoGrade(): void
    {
        $question = $this->question;

        if (!$question->isAutoGradable()) {
            return;
        }

        $isCorrect = $question->checkAnswer($this->answer ?? '');

        $this->update([
            'is_correct' => $isCorrect,
            'marks_obtained' => $isCorrect ? $question->marks : 0,
        ]);
    }

    public function manualGrade(float $marks, ?string $feedback = null): void
    {
        $this->update([
            'marks_obtained' => min($marks, $this->question->marks),
            'is_correct' => $marks >= $this->question->marks,
            'feedback' => $feedback,
        ]);
    }
}
