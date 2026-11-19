<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpExam extends Model
{
    use SoftDeletes;

    protected $table = 'sp_exams';

    protected $guarded = [];

    protected $casts = ['expires_at' => 'datetime'];

    public function targets()
    {
        return $this->hasMany(SpExamTarget::class, 'exam_id');
    }

    public function subjects()
    {
        return $this->hasMany(SpExamSubject::class, 'exam_id')->orderBy('order_index');
    }

    public function attempts()
    {
        return $this->hasMany(SpAttempt::class, 'exam_id');
    }
}
