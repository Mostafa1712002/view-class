<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name_ar', 'father_name_ar', 'grandfather_name_ar', 'family_name_ar',
        'first_name_en', 'father_name_en', 'grandfather_name_en', 'family_name_en',
        'passport_number',
        'birth_place',
        'nationality',
        'phone_secondary',
        'profile_photo',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
