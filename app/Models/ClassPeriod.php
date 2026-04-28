<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'substitute_teacher_id',
        'grade_level',
        'class_id',
        'subject_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ClassRoom::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }
}
