<?php

namespace App\Modules\Admissions\Services;

use App\Modules\Admissions\Models\AdmissionFormField;
use App\Modules\Admissions\Models\AdmissionInfoSection;
use App\Modules\Admissions\Models\AdmissionSchoolSetting;
use Illuminate\Support\Collection;

/**
 * Centralises per-school admission settings: form fields, info sections and the
 * school registration settings row. Defaults are seeded lazily on first access
 * (NOT in a migration — avoids cross-tenant seeding for schools that don't exist
 * yet and keeps the module self-contained).
 */
class AdmissionSettingsService
{
    /** Get (and lazily seed) the configurable form fields for a school, ordered. */
    public function fields(int $schoolId): Collection
    {
        $existing = AdmissionFormField::where('school_id', $schoolId)->orderBy('sort_order')->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        foreach (AdmissionFormField::defaults() as $i => $def) {
            AdmissionFormField::create([
                'school_id'   => $schoolId,
                'field_key'   => $def['key'],
                'label'       => $def['label'],
                'is_visible'  => true,
                'is_required' => $def['required'],
                'sort_order'  => $i,
            ]);
        }

        return AdmissionFormField::where('school_id', $schoolId)->orderBy('sort_order')->get();
    }

    /** Visible fields only — drives the public form render + required validation. */
    public function visibleFields(int $schoolId): Collection
    {
        return $this->fields($schoolId)->where('is_visible', true)->values();
    }

    /** Get (and lazily seed) the info sections for a school, ordered. */
    public function sections(int $schoolId): Collection
    {
        $existing = AdmissionInfoSection::where('school_id', $schoolId)->orderBy('sort_order')->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        foreach (AdmissionInfoSection::defaults() as $i => $title) {
            AdmissionInfoSection::create([
                'school_id'  => $schoolId,
                'title'      => $title,
                'content'    => null,
                'sort_order' => $i,
                'is_active'  => true,
            ]);
        }

        return AdmissionInfoSection::where('school_id', $schoolId)->orderBy('sort_order')->get();
    }

    /** Get (and lazily create) the settings row for a school. */
    public function settings(int $schoolId): AdmissionSchoolSetting
    {
        return AdmissionSchoolSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['registration_enabled' => true]
        );
    }
}
