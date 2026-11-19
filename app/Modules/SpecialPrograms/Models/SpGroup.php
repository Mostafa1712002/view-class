<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpGroup extends Model
{
    use SoftDeletes;

    protected $table = 'sp_groups';

    protected $guarded = [];

    public function enrollments()
    {
        return $this->hasMany(SpEnrollment::class, 'group_id');
    }
}
