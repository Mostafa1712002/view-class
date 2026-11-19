<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpQuestion extends Model
{
    protected $table = 'sp_questions';

    protected $guarded = [];

    protected $casts = ['has_image' => 'boolean', 'is_active' => 'boolean', 'merged_source_ids' => 'array'];

    public function subject()
    {
        return $this->belongsTo(SpSubject::class, 'sp_subject_id');
    }

    public function skill()
    {
        return $this->belongsTo(SpSkill::class, 'sp_skill_id');
    }
}
