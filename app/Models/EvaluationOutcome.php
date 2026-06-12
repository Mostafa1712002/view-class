<?php

namespace App\Models;

use App\Modules\Evaluation\Enums\OutcomeApprovalStatus;
use App\Modules\Evaluation\Enums\OutcomeMethod;
use App\Modules\Evaluation\Enums\OutcomeSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase C (#205) — Educational outcome record.
 *
 * Note on $fillable: approval_status, final_average, method_used, scores_sum,
 * registered_count, present_count, and absent_count are EXCLUDED from mass-assignment.
 * They are written only by the trusted ComputeEducationalOutcome and
 * RecomputeEducationalOutcome actions via explicit property assignment, so a crafted
 * request cannot bypass the computation logic.
 */
class EvaluationOutcome extends Model
{
    use SoftDeletes;

    protected $table = 'evaluation_outcomes';

    protected $fillable = [
        'school_id',
        'educational_company_id',
        'teacher_id',
        'subject_id',
        'grade_level',
        'class_label',
        'test_name',
        'test_type',
        'source',
        'students',
        'test_date',
        'imported_at',
        'computed_by',
    ];

    protected $casts = [
        'students'            => 'array',
        'test_date'           => 'date',
        'imported_at'         => 'datetime',
        'last_recomputed_at'  => 'datetime',
        'approval_status'     => OutcomeApprovalStatus::class,
        'source'              => OutcomeSource::class,
        'method_used'         => OutcomeMethod::class,
        'registered_count'    => 'integer',
        'present_count'       => 'integer',
        'absent_count'        => 'integer',
        'scores_sum'          => 'decimal:2',
        'final_average'       => 'decimal:2',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function computedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'computed_by');
    }

    // -----------------------------------------------------------------------
    // Convenience
    // -----------------------------------------------------------------------

    public function isApproved(): bool
    {
        return $this->approval_status === OutcomeApprovalStatus::Approved;
    }
}
