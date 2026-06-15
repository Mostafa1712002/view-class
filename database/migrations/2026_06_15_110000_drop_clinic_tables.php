<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes all clinic-related data as part of dropping the clinic feature.
 *
 * NOTE: This codebase never created any clinic-specific tables (no migration
 * defines `clinics`, `clinic_*`, etc.). The clinic feature existed only as a
 * sidebar placeholder. The single clinic DB artifact is the default
 * "Clinic Officer" job title (job_titles.slug = 'clinic', school_id IS NULL).
 *
 * The dropIfExists calls below are defensive no-ops in case an environment ever
 * created clinic tables out of band.
 */
return new class extends Migration {
    public function up(): void
    {
        // Defensive: drop any clinic tables if they happen to exist anywhere.
        Schema::dropIfExists('clinic_referrals');
        Schema::dropIfExists('clinic_diagnoses');
        Schema::dropIfExists('clinic_medical_records');
        Schema::dropIfExists('clinic_vaccinations');
        Schema::dropIfExists('clinic_medicines');
        Schema::dropIfExists('clinic_diseases');
        Schema::dropIfExists('clinics');

        // Remove the default "Clinic Officer" job title (system-wide, not tied
        // to a school). Scoped delete; leaves all other default titles intact.
        DB::table('job_titles')
            ->whereNull('school_id')
            ->where('slug', 'clinic')
            ->delete();
    }

    public function down(): void
    {
        // Re-insert the default clinic job title for reversibility.
        $now = now();
        DB::table('job_titles')->updateOrInsert(
            ['school_id' => null, 'slug' => 'clinic'],
            [
                'school_id'  => null,
                'slug'       => 'clinic',
                'name_ar'    => 'مسؤول عيادة',
                'name_en'    => 'Clinic Officer',
                'sort_order' => 6,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Clinic tables are intentionally not recreated (they never existed).
    }
};
