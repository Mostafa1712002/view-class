<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyWeek extends Model
{
    protected $fillable = [
        'academic_term_id',
        'name',
        'start_date',
        'end_date',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function isCurrent(): bool
    {
        $today = now('Asia/Riyadh')->toDateString();

        return $this->start_date && $this->end_date
            && $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    public function scopeCurrent($query)
    {
        $today = now('Asia/Riyadh')->toDateString();

        return $query->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }
}
