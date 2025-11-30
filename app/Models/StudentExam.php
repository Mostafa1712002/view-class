<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentExam extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'started_at',
        'submitted_at',
        'score',
        'percentage',
        'status',
        'attempt_number',
        'teacher_feedback',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'attempt_number' => 'integer',
    ];

    public const STATUSES = [
        'not_started' => 'لم يبدأ',
        'in_progress' => 'جاري',
        'submitted' => 'تم التسليم',
        'graded' => 'تم التصحيح',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'not_started' => 'bg-secondary',
            'in_progress' => 'bg-warning',
            'submitted' => 'bg-info',
            'graded' => 'bg-success',
            default => 'bg-secondary',
        };
    }

    public function isPassed(): bool
    {
        if ($this->status !== 'graded' || !$this->exam->pass_marks) {
            return false;
        }
        return $this->score >= $this->exam->pass_marks;
    }

    public function getGradeLabel(): string
    {
        if ($this->percentage === null) {
            return '-';
        }

        return match(true) {
            $this->percentage >= 90 => 'ممتاز',
            $this->percentage >= 80 => 'جيد جداً',
            $this->percentage >= 70 => 'جيد',
            $this->percentage >= 60 => 'مقبول',
            default => 'راسب',
        };
    }

    public function calculateScore(): void
    {
        $totalScore = $this->answers()->sum('marks_obtained');
        $totalMarks = $this->exam->total_marks;

        $this->score = $totalScore;
        $this->percentage = $totalMarks > 0 ? ($totalScore / $totalMarks) * 100 : 0;
        $this->save();
    }

    public function start(): void
    {
        $this->update([
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    public function submit(): void
    {
        $this->update([
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);
    }

    public function markAsGraded(): void
    {
        $this->calculateScore();
        $this->update(['status' => 'graded']);
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->started_at || !$this->exam->duration_minutes) {
            return null;
        }

        $endTime = $this->started_at->addMinutes($this->exam->duration_minutes);
        $remaining = now()->diffInSeconds($endTime, false);

        return max(0, $remaining);
    }

    public function isTimeUp(): bool
    {
        $remaining = $this->getRemainingTime();
        return $remaining !== null && $remaining <= 0;
    }
}
