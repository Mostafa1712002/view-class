<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * === Anti-cheat card (ac) ===
 * A single recorded exit / focus-loss event during an exam attempt.
 */
class ExamExitAttempt extends Model
{
    protected $fillable = [
        'student_exam_id',
        'exam_id',
        'student_id',
        'attempt_type',
        'attempt_count',
        'auto_ended',
        'device',
        'browser',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'auto_ended' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public const TYPES = [
        'tab_hidden' => 'تغيير التبويب',
        'window_blur' => 'فقدان تركيز النافذة',
        'beforeunload' => 'محاولة مغادرة الصفحة',
        'multi_tab' => 'فتح تبويب آخر',
        'back_navigation' => 'محاولة الرجوع',
    ];

    public function studentExam(): BelongsTo
    {
        return $this->belongsTo(StudentExam::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->attempt_type] ?? $this->attempt_type;
    }
}
