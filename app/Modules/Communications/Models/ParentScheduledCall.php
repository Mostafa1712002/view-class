<?php

namespace App\Modules\Communications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Parent CRM scheduled call (Sprint 10, Trello #269).
 */
class ParentScheduledCall extends Model
{
    use SoftDeletes;

    protected $table = 'parent_scheduled_calls';

    protected $fillable = [
        'school_id', 'parent_id', 'call_date', 'call_time', 'call_type',
        'purpose', 'outcome', 'answered', 'notes', 'followup_at',
        'assigned_to', 'status', 'created_by',
    ];

    protected $casts = [
        'call_date' => 'date',
        'answered' => 'boolean',
        'followup_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
