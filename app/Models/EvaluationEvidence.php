<?php

namespace App\Models;

use App\Modules\Evaluation\Enums\EvidenceSource;
use App\Modules\Evaluation\Enums\EvidenceStatus;
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

    // Phase B (#204) — approval-workflow fields (status, source, reviewed_by,
    // reviewed_at, review_note) are deliberately NOT mass-assignable. They are set
    // only by trusted server code (ReviewEvidence; future system/auto sources) via
    // explicit assignment, so a crafted upload request cannot pre-approve evidence.

    protected $casts = [
        'size'               => 'integer',
        'visible_to_subject' => 'boolean',
        // Phase B (#204)
        'status'      => EvidenceStatus::class,
        'source'      => EvidenceSource::class,
        'reviewed_at' => 'datetime',
    ];

    public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class, 'evaluation_id'); }
    public function item(): BelongsTo { return $this->belongsTo(EvaluationItem::class, 'item_id'); }
    public function indicator(): BelongsTo { return $this->belongsTo(EvaluationIndicator::class, 'indicator_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function file(): BelongsTo { return $this->belongsTo(File::class, 'file_id'); }

    /** Phase B — reviewer who approved/rejected this evidence. */
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    /** Convenience: is this evidence approved and therefore eligible to unlock scoring? */
    public function isApproved(): bool
    {
        return $this->status === EvidenceStatus::Approved;
    }
}
