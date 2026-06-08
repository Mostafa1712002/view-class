<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EvaluationAssignment extends Model
{
    protected $fillable = ['form_id', 'evaluator_id', 'status', 'assigned_at'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
    public function evaluator(): BelongsTo { return $this->belongsTo(User::class, 'evaluator_id'); }

    /** Targets this evaluator is responsible for. */
    public function targets(): BelongsToMany
    {
        return $this->belongsToMany(
            EvaluationTarget::class,
            'evaluation_assignment_targets',
            'assignment_id',
            'target_id'
        );
    }
}
