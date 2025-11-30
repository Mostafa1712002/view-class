<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'class_id',
        'academic_year_id',
        'semester',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SchedulePeriod::class);
    }

    // الخصائص المحسوبة
    public function getSemesterLabelAttribute(): string
    {
        return match($this->semester) {
            'first' => 'الفصل الأول',
            'second' => 'الفصل الثاني',
            default => $this->semester,
        };
    }

    // الدوال المساعدة
    public function getPeriodsByDay(int $dayOfWeek): \Illuminate\Database\Eloquent\Collection
    {
        return $this->periods()->where('day_of_week', $dayOfWeek)->orderBy('period_number')->get();
    }

    public function getPeriod(int $dayOfWeek, int $periodNumber): ?SchedulePeriod
    {
        return $this->periods()->where('day_of_week', $dayOfWeek)->where('period_number', $periodNumber)->first();
    }

    // النطاقات
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForAcademicYear($query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    public function scopeForSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }
}
