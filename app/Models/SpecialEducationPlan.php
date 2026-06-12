<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialEducationPlan extends Model
{
    use SoftDeletes;

    protected $table = 'special_education_plans';

    protected $fillable = [
        'se_student_id',
        'school_id',
        'title',
        'goals',
        'accommodations',
        'start_date',
        'review_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'review_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function seStudent(): BelongsTo
    {
        return $this->belongsTo(SpecialEducationStudent::class, 'se_student_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function statusLabel(): string
    {
        return __('special_education.plan_status_' . $this->status);
    }
}
