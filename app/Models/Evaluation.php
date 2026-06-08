<?php

namespace App\Models;

use App\Modules\Evaluation\Enums\EvaluationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_id', 'snapshot_id', 'evaluator_id', 'subject_type', 'subject_id', 'school_id',
        'class_visit_id', 'status', 'total_score', 'max_score', 'percentage', 'grade_label',
        'items_completed', 'indicators_completed', 'evidence_count', 'general_notes',
        'submitted_at', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'status'               => EvaluationStatus::class,
        'total_score'          => 'decimal:2',
        'max_score'            => 'decimal:2',
        'percentage'           => 'decimal:2',
        'items_completed'      => 'integer',
        'indicators_completed' => 'integer',
        'evidence_count'       => 'integer',
        'submitted_at'         => 'datetime',
        'approved_at'          => 'datetime',
    ];

    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
    public function snapshot(): BelongsTo { return $this->belongsTo(EvaluationFormSnapshot::class, 'snapshot_id'); }
    public function evaluator(): BelongsTo { return $this->belongsTo(User::class, 'evaluator_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(User::class, 'subject_id'); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function classVisit(): BelongsTo { return $this->belongsTo(ClassVisit::class, 'class_visit_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function responses(): HasMany { return $this->hasMany(EvaluationResponse::class, 'evaluation_id'); }
    public function evidences(): HasMany { return $this->hasMany(EvaluationEvidence::class, 'evaluation_id'); }
    public function comments(): HasMany { return $this->hasMany(EvaluationComment::class, 'evaluation_id'); }
}
