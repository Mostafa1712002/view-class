<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Classes take their gender from the school (card MX0W71Kh). One-time copy of
 * schools.student_gender onto every existing class; nothing is deleted.
 * Not reversible: the old per-class values are kept only in the pre-deploy backup.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schools = DB::table('schools')->whereNotNull('student_gender')->pluck('student_gender', 'id');
        foreach ($schools as $schoolId => $gender) {
            DB::table('classes')
                ->whereIn('section_id', DB::table('sections')->where('school_id', $schoolId)->select('id'))
                ->update(['gender' => $gender]);
        }
    }

    public function down(): void
    {
        // No-op: see the class docblock.
    }
};
