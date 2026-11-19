<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpAttempt extends Model
{
    protected $table = 'sp_attempts';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime', 'deadline_at' => 'datetime', 'ended_at' => 'datetime',
        'last_seen_at' => 'datetime', 'completed_event_at' => 'datetime', 'score_pct' => 'float',
    ];

    public function exam()
    {
        return $this->belongsTo(SpExam::class, 'exam_id');
    }

    public function examSubject()
    {
        return $this->belongsTo(SpExamSubject::class, 'exam_subject_id');
    }

    public function items()
    {
        return $this->hasMany(SpAttemptItem::class, 'attempt_id')->orderBy('order_index');
    }
}
