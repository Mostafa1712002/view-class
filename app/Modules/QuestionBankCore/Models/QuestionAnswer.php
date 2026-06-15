<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\BankQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Normalized answer for a bank question (#258). Lives alongside the legacy
 * bank_questions.answer_data JSON (not yet migrated). question_id → bank_questions.
 */
class QuestionAnswer extends Model
{
    protected $table = 'question_answers';

    protected $fillable = [
        'question_id',
        'answer_text',
        'answer_image',
        'answer_content_type',
        'is_correct',
        'sort_order',
        'blank_number',
        'column_a_text',
        'column_a_image',
        'column_b_text',
        'column_b_image',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(BankQuestion::class, 'question_id');
    }
}
