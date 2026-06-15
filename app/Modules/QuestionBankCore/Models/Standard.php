<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\Domain;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Educational standard (المعيار) — sits under a subject/domain (#258).
 */
class Standard extends Model
{
    use SoftDeletes;

    protected $table = 'standards';

    protected $fillable = [
        'subject_id',
        'domain_id',
        'code',
        'name',
        'sort_order',
        'status',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
