<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionImportBatch extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = ['pending', 'previewed', 'completed', 'failed'];

    protected $fillable = [
        'question_bank_id',
        'school_id',
        'original_filename',
        'stored_path',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'preview_data',
        'created_by',
    ];

    protected $casts = [
        'preview_data' => 'array',
        'total_rows' => 'integer',
        'imported_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(QuestionImportError::class, 'import_batch_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(BankQuestion::class, 'import_batch_id');
    }
}
