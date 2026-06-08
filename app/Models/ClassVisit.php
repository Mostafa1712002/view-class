<?php

namespace App\Models;

use App\Modules\Evaluation\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassVisit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'supervisor_id', 'teacher_id', 'subject_id', 'stage_id',
        'class_room_id', 'section_id', 'period_id', 'form_id', 'evaluation_id',
        'visit_type', 'notify_teacher', 'pre_notes', 'visit_date', 'visit_time', 'status',
    ];

    protected $casts = [
        'status'         => VisitStatus::class,
        'notify_teacher' => 'boolean',
        'visit_date'     => 'date',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function supervisor(): BelongsTo { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class, 'class_room_id'); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class, 'section_id'); }
    public function form(): BelongsTo { return $this->belongsTo(EvaluationForm::class, 'form_id'); }
    public function evaluation(): BelongsTo { return $this->belongsTo(Evaluation::class, 'evaluation_id'); }
}
