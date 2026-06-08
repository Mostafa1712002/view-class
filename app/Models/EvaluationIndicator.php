<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationIndicator extends Model
{
    protected $fillable = [
        'item_id', 'form_id', 'level_id', 'text', 'description', 'sort_order',
        'is_required', 'needs_note', 'needs_evidence', 'evidence_required', 'status',
    ];

    protected $casts = [
        'sort_order'        => 'integer',
        'is_required'       => 'boolean',
        'needs_note'        => 'boolean',
        'needs_evidence'    => 'boolean',
        'evidence_required' => 'boolean',
    ];

    public function item(): BelongsTo { return $this->belongsTo(EvaluationItem::class, 'item_id'); }
    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
    public function level(): BelongsTo { return $this->belongsTo(EvaluationLevel::class, 'level_id'); }
}
