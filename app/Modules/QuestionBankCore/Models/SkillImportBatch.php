<?php

namespace App\Modules\QuestionBankCore\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * #248 — staging row for the skills Excel import (preview → confirm).
 */
class SkillImportBatch extends Model
{
    protected $table = 'skill_import_batches';

    protected $fillable = [
        'school_id',
        'original_filename',
        'stored_path',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_rows',
        'status',
        'preview_data',
        'created_by',
    ];

    protected $casts = [
        'preview_data' => 'array',
    ];
}
