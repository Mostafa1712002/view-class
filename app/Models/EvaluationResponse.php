<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationResponse extends Model
{
    protected $fillable = [
        'evaluation_id', 'item_id', 'indicator_id', 'level_id', 'checklist_value', 'score', 'note',
    ];

    protected $casts = [
        'checklist_value' => 'boolean',
        'score'           => 'decimal:2',
    ];

    public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class, 'evaluation_id'); }
    public function item(): BelongsTo { return $this->belongsTo(EvaluationItem::class, 'item_id'); }
    public function indicator(): BelongsTo { return $this->belongsTo(EvaluationIndicator::class, 'indicator_id'); }
    public function level(): BelongsTo { return $this->belongsTo(EvaluationLevel::class, 'level_id'); }
}
