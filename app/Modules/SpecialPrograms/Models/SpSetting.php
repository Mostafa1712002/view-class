<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpSetting extends Model
{
    protected $table = 'sp_settings';

    protected $guarded = [];

    protected $casts = ['level_bands' => 'array', 'grade_levels' => 'array', 'allow_school_edit' => 'boolean'];
}
