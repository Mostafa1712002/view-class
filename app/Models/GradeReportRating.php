<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeReportRating extends Model
{
    protected $fillable = [
        'grade_report_id',
        'label',
        'min_score',
        'max_score',
        'sort_order',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class);
    }
}
