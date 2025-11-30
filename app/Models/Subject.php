<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'school_id',
        'description',
        'is_core',
        'grade_levels',
        'is_active',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_active' => 'boolean',
        'grade_levels' => 'array',
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
}
