<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\AcademicTerm;
use App\Models\StudyWeek;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Educational skill (المهارة) — classifies questions by subject/term/week (#248).
 * semester_id → academic_terms, week_id → study_weeks.
 */
class Skill extends Model
{
    use SoftDeletes;

    protected $table = 'skills';

    protected $fillable = [
        'school_id',
        'name',
        'subject_id',
        'semester_id',
        'week_id',
        'skill_type',
        'is_tahsili',
        'is_ability',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_tahsili' => 'boolean',
        'is_ability' => 'boolean',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'semester_id');
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(StudyWeek::class, 'week_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SkillAssignment::class);
    }
}
