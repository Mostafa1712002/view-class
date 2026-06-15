<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * #255 — an electronic or paper exam built from bank questions (module-owned,
 * separate from the legacy `exams` table).
 */
class QbExam extends Model
{
    use SoftDeletes;

    protected $table = 'qb_exams';

    public const DELIVERY_ELECTRONIC = 'electronic';

    public const DELIVERY_PAPER = 'paper';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_STOPPED = 'stopped';

    protected $fillable = [
        'school_id', 'title', 'description', 'delivery_type', 'subject_id', 'semester_id',
        'starts_at', 'ends_at', 'duration_minutes', 'selection_strategy', 'questions_target',
        'difficulty_distribution', 'allow_direct_access', 'show_result_immediately',
        'allow_retake', 'shuffle_questions', 'shuffle_answers', 'pass_score',
        'status', 'is_published', 'created_by',
    ];

    protected $casts = [
        'difficulty_distribution' => 'array',
        'allow_direct_access' => 'boolean',
        'show_result_immediately' => 'boolean',
        'allow_retake' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_answers' => 'boolean',
        'is_published' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'pass_score' => 'float',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(QbExamQuestion::class, 'qb_exam_id')->orderBy('sort_order');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(QbExamTarget::class, 'qb_exam_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
