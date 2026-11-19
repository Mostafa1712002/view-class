<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpExamSubject extends Model
{
    protected $table = 'sp_exam_subjects';

    protected $guarded = [];

    protected $casts = ['weeks' => 'array', 'question_grade_levels' => 'array'];

    public function exam()
    {
        return $this->belongsTo(SpExam::class, 'exam_id');
    }

    public function subject()
    {
        return $this->belongsTo(SpSubject::class, 'sp_subject_id');
    }

    public function skillShares()
    {
        return $this->hasMany(SpSkillShare::class, 'exam_subject_id');
    }
}
