<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationItem extends Model
{
    protected $fillable = [
        'form_id', 'name', 'description', 'sort_order', 'weight', 'max_score',
        'is_required', 'needs_evidence', 'evidence_required', 'allow_note',
        'visible_to_evaluator_only', 'visible_to_subject_after_result', 'status',
    ];

    protected $casts = [
        'sort_order'                      => 'integer',
        'weight'                          => 'decimal:2',
        'max_score'                       => 'decimal:2',
        'is_required'                     => 'boolean',
        'needs_evidence'                  => 'boolean',
        'evidence_required'               => 'boolean',
        'allow_note'                      => 'boolean',
        'visible_to_evaluator_only'       => 'boolean',
        'visible_to_subject_after_result' => 'boolean',
    ];

    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
    public function indicators(): HasMany { return $this->hasMany(EvaluationIndicator::class, 'item_id')->orderBy('sort_order'); }

    public function isActive(): bool { return $this->status === 'active'; }
}
