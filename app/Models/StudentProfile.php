<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name', 'father_name', 'grandfather_name', 'last_name',
        'first_name_en', 'father_name_en', 'grandfather_name_en', 'last_name_en',
        'fingerprint_id', 'seat_number', 'passport_number', 'nationality',
        'academic_id', 'birth_place', 'admission_year',
        'previous_school', 'enrollment_date',
        'father_national_id', 'mother_national_id', 'mother_full_name',
        'home_phone', 'address',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'admission_year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
