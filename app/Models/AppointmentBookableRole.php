<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentBookableRole extends Model
{
    protected $fillable = [
        'school_id',
        'label',
        'target_type',
        'target_id',
        'is_active',
        'sort',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort'      => 'integer',
            'target_id' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Human-readable target type label.
     */
    public function getTargetTypeLabelAttribute(): string
    {
        return match ($this->target_type) {
            'role'           => __('appointments.target_type_role'),
            'job_title'      => __('appointments.target_type_job_title'),
            'user'           => __('appointments.target_type_user'),
            'subject_teacher'=> __('appointments.target_type_subject_teacher'),
            default          => $this->target_type,
        };
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForSchool($query, ?int $schoolId)
    {
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('id');
    }
}
