<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject_id',
        'class_id',
        'academic_year_id',
        'title',
        'description',
        'instructions',
        'max_score',
        'due_date',
        'due_time',
        'allow_late_submission',
        'late_penalty_percent',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_time' => 'datetime:H:i',
        'max_score' => 'decimal:2',
        'late_penalty_percent' => 'decimal:2',
        'allow_late_submission' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->due_time) {
            return now()->greaterThan($this->due_date->setTimeFromTimeString($this->due_time->format('H:i:s')));
        }
        return now()->greaterThan($this->due_date->endOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'published' => 'منشور',
            'closed' => 'مغلق',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'published' => 'success',
            'closed' => 'danger',
            default => 'primary',
        };
    }

    public function getSubmissionStatsAttribute(): array
    {
        $total = User::whereHas('classEnrollments', function ($q) {
            $q->where('class_id', $this->class_id)
                ->where('academic_year_id', $this->academic_year_id);
        })->count();

        $submitted = $this->submissions()->whereIn('status', ['submitted', 'graded'])->count();
        $graded = $this->submissions()->where('status', 'graded')->count();

        return [
            'total' => $total,
            'submitted' => $submitted,
            'graded' => $graded,
            'pending' => $total - $submitted,
            'submission_rate' => $total > 0 ? round(($submitted / $total) * 100, 1) : 0,
        ];
    }
}
