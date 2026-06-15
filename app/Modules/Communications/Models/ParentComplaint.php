<?php

namespace App\Modules\Communications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Parent CRM complaint (Sprint 10, Trello #269).
 */
class ParentComplaint extends Model
{
    use SoftDeletes;

    protected $table = 'parent_complaints';

    protected $fillable = [
        'school_id', 'parent_id', 'student_id', 'code', 'type',
        'complaint_date', 'purpose', 'details', 'action_required', 'actions_taken',
        'priority', 'assigned_to', 'status', 'attachment_path', 'created_by',
    ];

    protected $casts = [
        'complaint_date' => 'date',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
