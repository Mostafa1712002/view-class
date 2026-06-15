<?php

namespace App\Modules\QuestionBankCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a skill to a compound/school/grade/class (#248). grade_id is a loose
 * grade-level reference (no hard FK — grade-level lives as classes.grade_level).
 */
class SkillAssignment extends Model
{
    protected $table = 'skill_assignments';

    protected $fillable = [
        'skill_id',
        'compound_id',
        'school_id',
        'grade_id',
        'class_id',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
