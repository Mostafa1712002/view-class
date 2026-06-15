<?php

namespace App\Modules\Communications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Parent CRM school visit (Sprint 10, Trello #269).
 */
class ParentSchoolVisit extends Model
{
    use SoftDeletes;

    protected $table = 'parent_school_visits';

    protected $fillable = [
        'school_id', 'parent_id', 'student_id', 'visit_date', 'visit_time',
        'reason', 'met_staff_id', 'summary', 'next_action', 'followup_date',
        'status', 'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'followup_date' => 'date',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function metStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'met_staff_id');
    }
}
