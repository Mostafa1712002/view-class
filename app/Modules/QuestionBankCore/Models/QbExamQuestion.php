<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\BankQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * #255 — a frozen snapshot of a bank question inside a qb_exam. Editing the source
 * BankQuestion does NOT change this row.
 */
class QbExamQuestion extends Model
{
    protected $table = 'qb_exam_questions';

    protected $fillable = [
        'qb_exam_id', 'bank_question_id', 'question_type', 'body', 'attachment_path',
        'answer_snapshot', 'question_snapshot', 'marks', 'sort_order',
    ];

    protected $casts = [
        'answer_snapshot' => 'array',
        'question_snapshot' => 'array',
        'marks' => 'float',
        'sort_order' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(QbExam::class, 'qb_exam_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(BankQuestion::class, 'bank_question_id');
    }
}
