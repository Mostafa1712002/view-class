<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationLevel extends Model
{
    protected $fillable = ['form_id', 'label', 'value', 'percentage', 'sort_order'];

    protected $casts = [
        'value'      => 'decimal:2',
        'percentage' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
}
