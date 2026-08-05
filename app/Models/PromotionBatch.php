<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionBatch extends Model
{
    protected $fillable = [
        'school_id', 'source_year_id', 'destination_year_id', 'status',
        'summary', 'executed_by', 'executed_at', 'rolled_back_by', 'rolled_back_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PromotionBatchItem::class, 'batch_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sourceYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'source_year_id');
    }

    public function destinationYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'destination_year_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
