<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationEvidence extends Model
{
    protected $table = 'evaluation_evidences';

    protected $fillable = [
        'evaluation_id', 'item_id', 'indicator_id', 'type', 'file_id', 'url',
        'original_name', 'mime', 'size', 'description', 'internal_notes',
        'visible_to_subject', 'uploaded_by',
    ];

    protected $casts = [
        'size'               => 'integer',
        'visible_to_subject' => 'boolean',
    ];

    public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class, 'evaluation_id'); }
    public function item(): BelongsTo { return $this->belongsTo(EvaluationItem::class, 'item_id'); }
    public function indicator(): BelongsTo { return $this->belongsTo(EvaluationIndicator::class, 'indicator_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function file(): BelongsTo { return $this->belongsTo(File::class, 'file_id'); }
}
