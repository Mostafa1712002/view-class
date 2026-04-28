<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_en',
        'code',
        'school_id',
        'description',
        'is_core',
        'grade_levels',
        'section',
        'credit_hours',
        'certificate_order',
        'source',
        'template_subject_id',
        'is_active',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_active' => 'boolean',
        'grade_levels' => 'array',
        'credit_hours' => 'integer',
        'certificate_order' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_teacher', 'subject_id', 'teacher_id')
            ->withTimestamps();
    }

    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    public function scopeElective($query)
    {
        return $query->where('is_core', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGradeLevel($query, int $level)
    {
        return $query->whereJsonContains('grade_levels', $level);
    }

    public function isAvailableForGrade(int $level): bool
    {
        return in_array($level, $this->grade_levels ?? []);
    }

    public function units(): HasMany
    {
        return $this->hasMany(SubjectUnit::class)->orderBy('sort_order');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'template_subject_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'en' && ! empty($this->name_en)) {
            return $this->name_en;
        }
        return $this->name;
    }
}
