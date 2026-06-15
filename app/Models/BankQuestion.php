<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    // Card §8 — question review workflow states.
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = ['draft', 'pending_review', 'approved', 'rejected', 'archived'];

    protected $fillable = [
        'question_bank_id',
        'question_code',
        'question_content_type',
        'is_full_image_question',
        // QB rebuild (#258) — new classification columns. Additive; legacy code
        // never sets these, so mass-assignment behaviour is unchanged for it.
        'question_category',
        'subject_id',
        'grade_id',
        'class_id',
        'semester_id',
        'passage_id',
        'archived_at',
        'lesson_id',
        'unit_id',
        'week_id',
        'skill_id',
        'standard_id',
        'domain_id',
        'type',
        'body_ar',
        'body_en',
        'answer_data',
        'difficulty',
        'points',
        'attachment_path',
        'source',
        'explanation',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'rejected_reason',
        'imported_by',
        'import_batch_id',
        'external_platform',
        'external_id',
        'sync_status',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'answer_data' => 'array',
        'metadata' => 'array',
        'difficulty' => 'integer',
        'points' => 'decimal:2',
        'is_full_image_question' => 'boolean',
        'reviewed_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'archived_at' => 'datetime',
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

    /**
     * Skill taxonomy link (QB rebuild #248). Additive — legacy code ignores it.
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\QuestionBankCore\Models\Skill::class, 'skill_id');
    }

    /**
     * Normalized answers (QB rebuild #258). Kept in sync with the legacy
     * answer_data JSON; legacy views still read answer_data.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(\App\Modules\QuestionBankCore\Models\QuestionAnswer::class, 'question_id')
            ->orderBy('sort_order');
    }

    /**
     * Exam questions copied from this bank question (usage tracking + used-question guards).
     */
    public function examUses(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'source_bank_question_id');
    }

    /**
     * Whether this question is currently used in any exam (blocks hard-delete → archive instead).
     */
    public function isUsedInExam(): bool
    {
        return $this->examUses()->exists();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'import_batch_id');
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
