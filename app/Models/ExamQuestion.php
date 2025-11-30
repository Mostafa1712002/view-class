<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'marks',
        'explanation',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'marks' => 'decimal:2',
        'order' => 'integer',
    ];

    public const TYPES = [
        'multiple_choice' => 'اختيار من متعدد',
        'true_false' => 'صح أو خطأ',
        'short_answer' => 'إجابة قصيرة',
        'essay' => 'مقالي',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class, 'question_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, ['multiple_choice', 'true_false']);
    }

    public function checkAnswer(string $answer): bool
    {
        if (!$this->isAutoGradable()) {
            return false;
        }

        return strtolower(trim($answer)) === strtolower(trim($this->correct_answer));
    }

    public function getOptionsArray(): array
    {
        return $this->options ?? [];
    }
}
