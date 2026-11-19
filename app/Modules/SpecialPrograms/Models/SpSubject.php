<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpSubject extends Model
{
    public $timestamps = false;

    protected $table = 'sp_subjects';

    protected $guarded = [];

    protected $casts = ['picks_passages' => 'boolean'];

    public function skills()
    {
        return $this->hasMany(SpSkill::class, 'sp_subject_id');
    }

    public function questions()
    {
        return $this->hasMany(SpQuestion::class, 'sp_subject_id');
    }
}
