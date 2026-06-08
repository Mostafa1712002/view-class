<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationComment extends Model
{
    protected $fillable = ['evaluation_id', 'user_id', 'body'];

    public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class, 'evaluation_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
