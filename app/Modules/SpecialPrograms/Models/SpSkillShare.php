<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpSkillShare extends Model
{
    public $timestamps = false;

    protected $table = 'sp_exam_skill_shares';

    protected $guarded = [];

    public function skill()
    {
        return $this->belongsTo(SpSkill::class, 'sp_skill_id');
    }
}
