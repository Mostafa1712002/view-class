<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialEducationStudent extends Model
{
    use SoftDeletes;

    protected $table = 'special_education_students';

    protected $fillable = [
        'school_id',
        'student_id',
        'category',
        'diagnosis',
        'severity',
        'assigned_specialist',
        'status',
        'notes',
        'created_by',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_specialist');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(SpecialEducationPlan::class, 'se_student_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SpecialEducationNote::class, 'se_student_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function categoryLabel(): string
    {
        return __('special_education.category_' . $this->category);
    }

    public function statusLabel(): string
    {
        return __('special_education.student_status_' . $this->status);
    }

    public function severityLabel(): ?string
    {
        if (! $this->severity) {
            return null;
        }

        return __('special_education.severity_' . $this->severity);
    }
}
