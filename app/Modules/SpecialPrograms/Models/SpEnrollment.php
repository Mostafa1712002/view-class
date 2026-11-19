<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpEnrollment extends Model
{
    use SoftDeletes;

    protected $table = 'sp_enrollments';

    protected $guarded = [];

    protected $casts = ['enrolled_at' => 'datetime'];

    public function group()
    {
        return $this->belongsTo(SpGroup::class, 'group_id');
    }
}
