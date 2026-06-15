<?php

namespace App\Modules\QuestionBankCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * #255 — a (school, grade-level, class) the exam is targeted at. Supports the
 * card's multi-school/grade requirement that legacy single-class exams cannot.
 */
class QbExamTarget extends Model
{
    protected $table = 'qb_exam_targets';

    protected $fillable = ['qb_exam_id', 'school_id', 'grade_level', 'class_id'];

    protected $casts = ['grade_level' => 'integer'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(QbExam::class, 'qb_exam_id');
    }
}
