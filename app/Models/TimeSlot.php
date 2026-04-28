<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSlot extends Model
{
    protected $fillable = [
        'school_id',
        'period_no',
        'starts_at',
        'ends_at',
        'is_break',
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'period_no' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getRangeAttribute(): string
    {
        return sprintf('%s – %s', \Illuminate\Support\Str::of($this->starts_at)->limit(5, ''), \Illuminate\Support\Str::of($this->ends_at)->limit(5, ''));
    }
}
