<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyPlan extends Model
{
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
        'week_start_date',
        'week_end_date',
        'objectives',
        'topics',
        'activities',
        'resources',
        'assessment',
        'homework',
        'notes',
        'is_locked',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    // العلاقات
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

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // الخصائص المحسوبة
    public function getWeekLabelAttribute(): string
    {
        return $this->week_start_date->format('Y-m-d') . ' إلى ' . $this->week_end_date->format('Y-m-d');
    }

    public function getStatusAttribute(): string
    {
        return $this->is_locked ? 'مقفلة' : 'مفتوحة للتعديل';
    }

    public function getStatusClassAttribute(): string
    {
        return $this->is_locked ? 'bg-danger' : 'bg-success';
    }

    // الدوال المساعدة
    public function lock(User $user): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $user->id,
        ]);

        return true;
    }

    public function unlock(): bool
    {
        if (!$this->is_locked) {
            return false;
        }

        $this->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return true;
    }

    public function canEdit(User $user): bool
    {
        // المدراء يمكنهم التعديل دائماً
        if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
            return true;
        }

        // المعلم يمكنه التعديل فقط إذا كانت الخطة غير مقفلة
        return !$this->is_locked && $this->teacher_id === $user->id;
    }

    // النطاقات
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForWeek($query, $startDate)
    {
        return $query->where('week_start_date', $startDate);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeCurrentWeek($query)
    {
        $today = now();
        return $query->where('week_start_date', '<=', $today)
                     ->where('week_end_date', '>=', $today);
    }
}
