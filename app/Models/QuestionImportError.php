<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionImportError extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'errors',
        'raw',
    ];

    protected $casts = [
        'errors' => 'array',
        'raw' => 'array',
        'row_number' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'import_batch_id');
    }
}
