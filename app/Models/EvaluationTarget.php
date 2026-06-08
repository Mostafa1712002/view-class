<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationTarget extends Model
{
    protected $fillable = [
        'form_id', 'target_type', 'target_id', 'meta', 'added_after_publish', 'added_by',
    ];

    protected $casts = [
        'meta'                => 'array',
        'added_after_publish' => 'boolean',
    ];

    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }

    /** The evaluated entity (usually a User). */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
