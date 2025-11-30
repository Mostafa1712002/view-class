<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    use HasFactory;
    protected $table = 'classes';

    protected $fillable = [
        'name',
        'section_id',
        'academic_year_id',
        'grade_level',
        'division',
        'capacity',
        'room',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_student', 'class_id', 'student_id')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function weeklyPlans(): HasMany
    {
        return $this->hasMany(WeeklyPlan::class, 'class_id');
    }

    public function getActiveSchedule()
    {
        return $this->schedules()->active()->first();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} - {$this->division}";
    }

    public function getGradeLevelLabelAttribute(): string
    {
        $levels = [
            1 => 'الأول',
            2 => 'الثاني',
            3 => 'الثالث',
            4 => 'الرابع',
            5 => 'الخامس',
            6 => 'السادس',
        ];

        return $levels[$this->grade_level] ?? "الصف {$this->grade_level}";
    }

    public function getAvailableSeatsAttribute(): int
    {
        return $this->capacity - $this->students()->count();
    }

    public function hasAvailableSeats(): bool
    {
        return $this->available_seats > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSection($query, int $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeForAcademicYear($query, int $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }
}
