<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationAssignmentTarget extends Model
{
    protected $fillable = ['assignment_id', 'target_id'];

    public function assignment(): BelongsTo { return $this->belongsTo(EvaluationAssignment::class, 'assignment_id'); }
    public function target(): BelongsTo { return $this->belongsTo(EvaluationTarget::class, 'target_id'); }
}
