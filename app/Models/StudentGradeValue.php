<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGradeValue extends Model
{
    protected $fillable = [
        'grade_report_id',
        'grade_report_column_id',
        'student_id',
        'score',
        'note',
        'recorded_by',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(GradeReportColumn::class, 'grade_report_column_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
