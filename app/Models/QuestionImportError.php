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
        // QB rebuild (#254) — extended columns (migration qbcore_extend_import_tables)
        'question_code',
        'error_field',
        'error_message',
        'error_type',
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
