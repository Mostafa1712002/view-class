<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpTeacher extends Model
{
    use SoftDeletes;

    protected $table = 'sp_teachers';

    protected $guarded = [];

    public function subject()
    {
        return $this->belongsTo(SpSubject::class, 'sp_subject_id');
    }
}
