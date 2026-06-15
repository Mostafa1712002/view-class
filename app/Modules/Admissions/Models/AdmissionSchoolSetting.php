<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionSchoolSetting extends Model
{
    protected $table = 'admission_school_settings';

    protected $fillable = [
        'school_id', 'registration_enabled', 'form_title', 'link_token',
    ];

    protected $casts = [
        'registration_enabled' => 'boolean',
    ];
}
