<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankQuestion extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'true_false',
        'mcq',
        'essay',
        'matching',
        'fill_blank',
        'short',
    ];

    public const DIFFICULTIES = [
        1 => 'easy',
        2 => 'medium',
        3 => 'hard',
    ];

    public const STATUSES = ['draft', 'published', 'archived'];

    protected $fillable = [
        'question_bank_id',
        'lesson_id',
        'type',
        'body_ar',
        'body_en',
        'answer_data',
        'difficulty',
        'points',
        'attachment_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'answer_data' => 'array',
        'difficulty' => 'integer',
        'points' => 'decimal:2',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(SubjectLesson::class, 'lesson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayBodyAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'en' && ! empty($this->body_en)) {
            return $this->body_en;
        }
        return $this->body_ar;
    }
}
