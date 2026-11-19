<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpSkill extends Model
{
    protected $table = 'sp_skills';

    protected $guarded = [];

    public function subject()
    {
        return $this->belongsTo(SpSubject::class, 'sp_subject_id');
    }
}
