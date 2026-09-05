<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'educational_company_id',
        'name',
        'name_ar',
        'name_en',
        'branch',
        'branch_id',
        'sort_order',
        'educational_track',
        'stage',
        'city',
        'student_gender',
        'timezone',
        'default_language',
        'code',
        'logo',
        'cover_image',
        'address',
        'phone',
        'fax',
        'email',
        'website',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function educationalCompany(): BelongsTo
    {
        return $this->belongsTo(EducationalCompany::class);
    }

    /**
     * The school's educational complex (المجمع). Single source of truth is the
     * compound_school pivot — sync(array_filter([$id])) enforces at most one row
     * per school; do not add a schools.complex_id column (educational-companies-complexes card).
     */
    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\QuestionBankCore\Models\Compound::class, 'compound_school', 'school_id', 'compound_id')
            ->withTimestamps();
    }

    public function branchRecord(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\SchoolBranches\Models\SchoolBranch::class, 'branch_id');
    }

    public function smsSetting()
    {
        return $this->hasOne(\App\Modules\SmsServices\Models\SchoolSmsSetting::class);
    }

    public function smsSenders()
    {
        return $this->hasMany(\App\Modules\SmsServices\Models\SmsSender::class);
    }

    public function whatsappSetting()
    {
        return $this->hasOne(\App\Modules\Whatsapp\Models\SchoolWhatsappSetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function classes(): HasManyThrough
    {
        return $this->hasManyThrough(ClassRoom::class, Section::class);
    }

    public function currentAcademicYear(): ?AcademicYear
    {
        return $this->academicYears()->where('is_current', true)->first();
    }

    public function teachers(): HasMany
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'teacher');
        });
    }

    public function students(): HasMany
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        });
    }
}
