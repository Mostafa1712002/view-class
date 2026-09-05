<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationalCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'logo',
        'status',
    ];

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function compounds(): HasMany
    {
        return $this->hasMany(\App\Modules\QuestionBankCore\Models\Compound::class);
    }
}
