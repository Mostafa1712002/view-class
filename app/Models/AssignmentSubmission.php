<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id',
        'student_id',
        'content',
        'file_path',
        'file_name',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
        'submitted_at',
        'is_late',
        'status',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'لم يسلم',
            'submitted' => 'مسلم',
            'graded' => 'تم التقييم',
            'returned' => 'معاد',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'secondary',
            'submitted' => 'info',
            'graded' => 'success',
            'returned' => 'warning',
            default => 'primary',
        };
    }

    public function getScorePercentageAttribute(): ?float
    {
        if ($this->score === null || !$this->assignment) {
            return null;
        }

        return $this->assignment->max_score > 0
            ? round(($this->score / $this->assignment->max_score) * 100, 1)
            : 0;
    }

    public function calculateFinalScore(): ?float
    {
        if ($this->score === null) {
            return null;
        }

        if ($this->is_late && $this->assignment->late_penalty_percent > 0) {
            $penalty = $this->score * ($this->assignment->late_penalty_percent / 100);
            return max(0, $this->score - $penalty);
        }

        return $this->score;
    }
}
