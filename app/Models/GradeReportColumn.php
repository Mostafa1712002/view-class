<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeReportColumn extends Model
{
    protected $fillable = [
        'grade_report_id',
        'subject_id',
        'title',
        'type',
        'weight',
        'max_score',
        'pass_threshold',
        'formula',
        'sort_order',
        'is_in_total',
        'is_visible',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'pass_threshold' => 'decimal:2',
        'is_in_total' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
