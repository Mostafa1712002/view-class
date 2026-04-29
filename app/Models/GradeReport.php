<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'academic_term_id',
        'class_id',
        'type',
        'title',
        'grade_input_starts_at',
        'grade_input_ends_at',
        'calc_starts_at',
        'calc_ends_at',
        'opens_at',
        'closes_at',
        'include_behavior',
        'show_subject_bilingual',
        'visible_to_student',
        'visible_to_parent',
        'visible_to_teacher',
        'header_settings',
        'footer_settings',
        'created_by',
    ];

    protected $casts = [
        'grade_input_starts_at' => 'date',
        'grade_input_ends_at' => 'date',
        'calc_starts_at' => 'date',
        'calc_ends_at' => 'date',
        'opens_at' => 'date',
        'closes_at' => 'date',
        'include_behavior' => 'boolean',
        'show_subject_bilingual' => 'boolean',
        'visible_to_student' => 'boolean',
        'visible_to_parent' => 'boolean',
        'visible_to_teacher' => 'boolean',
        'header_settings' => 'array',
        'footer_settings' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(GradeReportColumn::class)->orderBy('sort_order');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(GradeReportRating::class)->orderBy('sort_order');
    }
}
