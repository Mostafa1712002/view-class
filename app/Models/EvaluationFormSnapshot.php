<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationFormSnapshot extends Model
{
    protected $fillable = ['form_id', 'version', 'payload', 'published_by', 'published_at'];

    protected $casts = [
        'version'      => 'integer',
        'payload'      => 'array',
        'published_at' => 'datetime',
    ];

    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
}
