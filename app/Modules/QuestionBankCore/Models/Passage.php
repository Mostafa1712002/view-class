<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reading passage (القطعة) — owns a set of passage questions (#258).
 */
class Passage extends Model
{
    use SoftDeletes;

    protected $table = 'passages';

    protected $fillable = [
        'question_bank_id',
        'passage_code',
        'passage_text',
        'passage_image',
        'subject_id',
        'semester_id',
        'week_id',
        'skill_id',
        'difficulty_level',
        'status',
        'created_by',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(BankQuestion::class, 'passage_questions', 'passage_id', 'question_id')
            ->withPivot('sort_order')
            ->orderBy('passage_questions.sort_order');
    }
}
