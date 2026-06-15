<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Targets a question bank to a compound/school/grade/class/teacher (#258).
 * grade_id is a loose grade-level reference (no hard FK).
 */
class QuestionBankAssignment extends Model
{
    protected $table = 'question_bank_assignments';

    protected $fillable = [
        'question_bank_id',
        'compound_id',
        'school_id',
        'grade_id',
        'class_id',
        'teacher_id',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }
}
