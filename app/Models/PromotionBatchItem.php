<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionBatchItem extends Model
{
    protected $fillable = [
        'batch_id', 'student_id', 'action',
        'from_section_id', 'from_class_id', 'to_section_id', 'to_class_id', 'reason',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PromotionBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
