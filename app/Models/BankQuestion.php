<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question_bank_id',
        'type',
        'body_ar',
        'body_en',
        'answer_data',
        'difficulty',
    ];

    protected $casts = [
        'answer_data' => 'array',
        'difficulty' => 'integer',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
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
