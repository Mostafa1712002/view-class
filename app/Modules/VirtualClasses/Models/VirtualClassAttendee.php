<?php

namespace App\Modules\VirtualClasses\Models;

use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-student entry/exit log for a virtual class. This is the SOURCE the recalc
 * action reads to compute duration → status, which it then mirrors into the
 * shared `attendances` table so parent/student reports surface it automatically.
 */
class VirtualClassAttendee extends Model
{
    protected $table = 'virtual_class_attendees';

    protected $fillable = [
        'virtual_class_id',
        'student_id',
        'school_id',
        'joined_at',
        'left_at',
        'duration_minutes',
        'attendance_status',
    ];

    protected $casts = [
        'joined_at'        => 'datetime',
        'left_at'          => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function virtualClass(): BelongsTo
    {
        return $this->belongsTo(VirtualClass::class, 'virtual_class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
