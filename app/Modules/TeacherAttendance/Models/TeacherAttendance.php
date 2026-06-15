<?php

namespace App\Modules\TeacherAttendance\Models;

use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherAttendance extends Model
{
    use SoftDeletes;

    protected $table = 'teacher_attendances';

    protected $fillable = [
        'teacher_id', 'school_id', 'academic_year_id', 'class_id', 'subject_id',
        'period', 'date', 'status', 'arrival_time', 'excuse_text', 'notes',
        'notified', 'recorded_by',
    ];

    protected $casts = [
        'date'     => 'date',
        'notified' => 'boolean',
        'period'   => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
